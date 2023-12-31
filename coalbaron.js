/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * coalbaron.js
 *
 * CoalBaron user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
], function (dojo, declare) {
  return declare('bgagame.coalbaron', [customgame.game], {
    constructor() {
      this._inactiveStates = [];
      this._notifications = [
        ['placeWorkers', null],
        ['spendMoney', 1300],
        ['giveTileTo', 1200],
        ['moveCoalToTile', 1000],
        // ["newTurn", 1000],
        // ["updateFirstPlayer", 500],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=800';
    },

    getSettingsConfig() {
      return {
        confirmMode: { type: 'pref', prefId: 103 },
      };
    },

    /**
     * Setup:
     *	This method set up the game user interface according to current game situation specified in parameters
     *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
     *
     * Params :
     *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
     */
    setup(gamedatas) {
      debug('SETUP', gamedatas);
      $('page-title').insertAdjacentHTML('beforeend', '<div id="box-reserve"></div>');
      this.setupCentralBoard();
      this.setupPlayers();
      this.setupInfoPanel();
      this.setupTiles();
      this.setupMeeples();

      // Create round counter
      this._roundCounter = this.createCounter('round-counter');
      this.updateTurn();

      this.inherited(arguments);
    },

    setupCentralBoard() {
      let container = $('coalbaron-board');
      const SPACES = {
        factory_1: ['tile', 'workers', 'title'],
        factory_2: ['tile', 'workers', 'title'],
        factory_3: ['tile', 'workers', 'title'],
        factory_4: ['tile', 'workers', 'title'],
        factory_5: ['title', 'workers', 'tile'],
        factory_6: ['title', 'workers', 'tile'],
        factory_7: ['title', 'workers', 'tile'],
        factory_8: ['title', 'workers', 'tile'],
        factory_draw: ['title', 'workers'],
        mining_4: ['title', 'workers'],
        mining_6: ['title', 'workers'],
        mining_8: ['title', 'workers'],
        mining_10: ['title', 'workers'],
        delivery_1: ['title', 'workers'],
        delivery_2: ['title', 'workers'],
        delivery_3: ['title', 'workers'],
        delivery_4: ['title', 'workers'],
        bank_1: ['workers'],
        bank_3: ['workers', 'title'],
        bank_4: ['workers', 'title'],
        bank_5: ['workers', 'title'],
        bank_6: ['workers', 'title'],
        order_1: ['title', 'workers', 'order'],
        order_2: ['title', 'workers', 'order'],
        order_3: ['title', 'workers', 'order'],
        order_4: ['title', 'workers', 'order'],
        order_draw: ['title', 'workers'],
        canteen: ['workers'],
      };
      const SPACES_NO_3P = ['factory_4', 'mining_8', 'bank_5', 'order_1'];
      const SPACES_NO_2P = ['factory_4', 'factory_8', 'bank_3', 'bank_5', 'mining_4', 'mining_8', 'order_1'];

      let nPlayers = Object.keys(this.gamedatas.players).length;
      Object.keys(SPACES).forEach((spaceId) => {
        let disabled = (nPlayers == 2 && SPACES_NO_2P.includes(spaceId)) || (nPlayers == 3 && SPACES_NO_3P.includes(spaceId));
        let space = `<div class='board-space ${disabled ? 'disabled' : ''}' id='${spaceId}'>`;
        SPACES[spaceId].forEach((content) => (space += `<div class='space-${content}-container'></div>`));
        space += '</div>';
        container.insertAdjacentHTML('beforeend', space);
      });
    },

    onEnteringState(stateName, args) {
      debug('Entering state: ' + stateName, args);
      if (this.isFastMode() && ![].includes(stateName)) return;

      if (args.args && args.args.descSuffix) {
        this.changePageTitle(args.args.descSuffix);
      }

      if (args.args && args.args.optionalAction) {
        let base = args.args.descSuffix ? args.args.descSuffix : '';
        this.changePageTitle(base + 'skippable');
      }

      if (!this._inactiveStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    onEnteringStateDraft(args) {
      Object.values(args.cards).forEach((card) => this.addCard(card, $('draft-container')));
    },

    onEnteringStatePlaceWorker(args) {
      let selectedSpace = null;
      Object.keys(args.nbrWorkersNeeded).forEach((spaceId) => {
        this.onClick(spaceId, () => {
          if (selectedSpace !== null) $(selectedSpace).classList.remove('selected');
          selectedSpace = spaceId;
          $(selectedSpace).classList.add('selected');
          this.addPrimaryActionButton('btnConfirm', '', () => this.takeAction('actPlaceWorker', { spaceId: selectedSpace }));
          $('btnConfirm').innerHTML = this.fsr(_('Confirm and place ${n} worker(s)'), { n: args.nbrWorkersNeeded[spaceId] });
        });
      });
    },

    notif_placeWorkers(n) {
      debug('Notif: placing workers', n);
      this._counters[n.args.player_id]['worker'].incValue(-n.args.n);
      Promise.all(
        n.args.workerIds.map((workerId, i) =>
          this.wait(200 * i).then(() =>
            this.slide(`meeple-${workerId}`, $(n.args.space).querySelector('.space-workers-container'))
          )
        )
      ).then(() => {
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
      });
    },

    ////////////////////////////////////////
    //  ____  _
    // |  _ \| | __ _ _   _  ___ _ __ ___
    // | |_) | |/ _` | | | |/ _ \ '__/ __|
    // |  __/| | (_| | |_| |  __/ |  \__ \
    // |_|   |_|\__,_|\__, |\___|_|  |___/
    //                |___/
    ////////////////////////////////////////

    setupPlayers() {
      let currentPlayerNo = 1;
      let nPlayers = 0;
      this._counters = {};
      this.forEachPlayer((player) => {
        let isCurrent = player.id == this.player_id;
        this.place('tplPlayerPanel', player, `player_panel_content_${player.color}`, 'after');

        this.place('tplPlayerBoard', player, 'coalbaron-player-boards-container');
        // player.biomes.forEach((biome) => {
        //   this.addBiome(biome);
        // });

        // if (player.hand !== null) {
        //   this.addBiome(player.hand, 'pending-biomes');
        //   $('pending-biomes-wrapper').classList.remove('empty');
        // }

        let pId = player.id;
        this._counters[pId] = {
          worker: this.createCounter(`counter-${pId}-worker`, player.workers),
          money: this.createCounter(`counter-${pId}-money`, player.money),
        };

        // Useful to order boards
        nPlayers++;
        if (isCurrent) currentPlayerNo = player.no;
      });

      // Order them
      this.forEachPlayer((player) => {
        let order = ((player.no - currentPlayerNo + nPlayers) % nPlayers) + 1;
        $(`board-${player.id}`).style.order = order;

        if (order == 1) {
          dojo.place('<div id="coalbaron-first-player"></div>', `overall_player_board_${player.id}`);
          this.addCustomTooltip('coalbaron-first-player', _('First player'));
        }
      });

      this.updateFirstPlayer();
    },

    updateFirstPlayer() {
      let pId = this.gamedatas.firstPlayer;
      // let container = $(`overall_player_board_${pId}`);
      // this.slide('coalbaron-first-player', container.querySelector('.first-player-holder'), {
      //   phantom: false,
      // });
    },

    getPlayerColor(pId) {
      return this.gamedatas.players[pId].color;
    },

    notif_updateFirstPlayer(n) {
      debug('Notif: updating first player', n);
      this.gamedatas.firstPlayer = n.args.pId;
      this.updateFirstPlayer();
    },

    tplPlayerBoard(player) {
      let content = '';

      return `<div class='coalbaron-board' id='board-${player.id}' data-color='${player.color}'>
         <div class='player-name' style='color:#${player.color}'>${player.name}</div>
         <div class='board-left-side'>
            <div class='completed-orders' id='completed-orders-${player.id}'></div>
            <div class='left-pit'>
              <div class='pit-level' id='left-pit-${player.id}-1'></div>
              <div class='pit-level' id='left-pit-${player.id}-2'><div class='pit-tile'></div></div>
              <div class='pit-level' id='left-pit-${player.id}-3'><div class='pit-tile'></div></div>
              <div class='pit-level' id='left-pit-${player.id}-4'></div>
            </div>
         </div>
         <div class='board-elevator'>
           <div class='elevator' id='elevator-${player.id}' data-y="0"></div>
         </div>
         <div class='board-right-side'>
            <div class='pending-orders' id='pending-orders-${player.id}'></div>
            <div class='right-pit'>
              <div class='pit-level' id='right-pit-${player.id}-1'><div class='pit-tile'></div></div>
              <div class='pit-level' id='right-pit-${player.id}-2'></div>
              <div class='pit-level' id='right-pit-${player.id}-3'></div>
              <div class='pit-level' id='right-pit-${player.id}-4'><div class='pit-tile'></div></div>
            </div>
         </div>
      </div>`;
    },

    /**
     * Player panel
     */

    tplPlayerPanel(player) {
      return `<div class='coalbaron-panel'>
        <div class="first-player-holder"></div>
        <div class='coalbaron-player-infos'>
          ${this.tplResourceCounter(player, 'worker')}
          ${this.tplResourceCounter(player, 'money')}
        </div>
      </div>`;
    },

    /**
     * Use this tpl for any counters that represent qty of meeples in "reserve", eg xtokens
     */
    tplResourceCounter(player, res, prefix = '') {
      return this.formatString(`
        <div class='player-resource resource-${res}'>
          <span id='${prefix}counter-${player.id}-${res}' 
            class='${prefix}resource-${res}'></span>${this.formatIcon(res)}
          <div class='reserve' id='${prefix}reserve-${player.id}-${res}'></div>
        </div>
      `);
    },

    gainPayMoney(pId, n, targetSource = null) {
      if (this.isFastMode()) {
        this._crystalCounters[pId].incValue(n);
        return Promise.resolve();
      }

      let elem = `<div id='money-animation'>
        ${Math.abs(n)}
        <div class="icon-container icon-container-money">
          <div class="coalbaron-icon icon-money"></div>
        </div>
      </div>`;
      $('page-content').insertAdjacentHTML('beforeend', elem);

      if (n > 0) {
        return this.slide('money-animation', `counter-${pId}-money`, {
          from: targetSource || 'page-title',
          destroy: true,
          phantom: false,
          duration: 1200,
        }).then(() => this._counters[pId]['money'].incValue(n));
      } else {
        this._counters[pId]['money'].incValue(n);
        return this.slide('money-animation', targetSource || 'page-title', {
          from: `counter-${pId}-money`,
          destroy: true,
          phantom: false,
          duration: 1200,
        });
      }
    },

    notif_spendMoney(n) {
      debug('Notif: spending money', n);
      this.gainPayMoney(n.args.player_id, -n.args.n);
    },

    ////////////////////////////////////////////////////////
    //  _____ _ _
    // |_   _(_) | ___  ___
    //   | | | | |/ _ \/ __|
    //   | | | | |  __/\__ \
    //   |_| |_|_|\___||___/
    //////////////////////////////////////////////////////////

    setupTiles() {
      // This function is refreshUI compatible
      let tileIds = this.gamedatas.tiles.map((tile) => {
        if (!$(`tile-${tile.id}`)) {
          this.addTile(tile);
        }

        let o = $(`tile-${tile.id}`);
        if (!o) return null;

        let container = this.getTileContainer(tile);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = tile.state;

        return tile.id;
      });
      document.querySelectorAll('.coalbaron-tile[id^="tile-"]').forEach((oTile) => {
        if (!tileIds.includes(parseInt(oTile.getAttribute('data-id')))) {
          this.destroy(oTile);
        }
      });
    },

    addTile(tile, location = null) {
      if ($('tile-' + tile.id)) return;

      let o = this.place('tplTile', tile, location == null ? this.getTileContainer(tile) : location);
      let tooltipDesc = this.getTileTooltip(tile);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getTileTooltip(tile) {
      return null;
    },

    tplTile(tile) {
      let type = tile.type.charAt(0).toLowerCase() + tile.type.substr(1);
      let color = ` data-color="${tile.color}" data-type="${tile.type}"`;
      return `<div class="coalbaron-tile coalbaron-icon icon-${type}" id="tile-${tile.id}" data-id="${tile.id}" data-type="${type}" ${color}></div>`;
    },

    getTileContainer(tile) {
      let t = tile.location.split('_');
      if (t[0] == 'factory') {
        return $(tile.location).querySelector('.space-tile-container');
      }
      if (tile.location == 'player') {
        return $(`${tile.x < 0 ? 'left' : 'right'}-pit-${tile.pId}-${tile.y}`);
      }
      console.error('Trying to get container of a tile', tile);
      return 'game_play_area';
    },

    notif_giveTileTo(n) {
      debug('Notif: putting tile to pit', n);
      this.slide(`tile-${n.args.tile.id}`, this.getTileContainer(n.args.tile));
    },

    ////////////////////////////////////////////////////////
    //  __  __                 _
    // |  \/  | ___  ___ _ __ | | ___  ___
    // | |\/| |/ _ \/ _ \ '_ \| |/ _ \/ __|
    // | |  | |  __/  __/ |_) | |  __/\__ \
    // |_|  |_|\___|\___| .__/|_|\___||___/
    //                  |_|
    //////////////////////////////////////////////////////////

    setupMeeples() {
      // This function is refreshUI compatible
      let meepleIds = this.gamedatas.meeples.map((meeple) => {
        if (!$(`meeple-${meeple.id}`)) {
          this.addMeeple(meeple);
        }

        let o = $(`meeple-${meeple.id}`);
        if (!o) return null;

        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = meeple.state;

        return meeple.id;
      });
      document.querySelectorAll('.coalbaron-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id')))) {
          this.destroy(oMeeple);
        }
      });
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;

      let o = this.place('tplMeeple', meeple, location == null ? this.getMeepleContainer(meeple) : location);
      let tooltipDesc = this.getMeepleTooltip(meeple);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getMeepleTooltip(meeple) {
      return null;
    },

    tplMeeple(meeple) {
      let type = meeple.type.charAt(0).toLowerCase() + meeple.type.substr(1);
      const PERSONAL = ['worker'];
      let color = PERSONAL.includes(type) ? ` data-color="${this.getPlayerColor(meeple.pId)}" ` : '';
      return `<div class="coalbaron-meeple coalbaron-icon icon-${type}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-type="${type}" ${color}></div>`;
    },

    getMeepleContainer(meeple) {
      let t = meeple.location.split('_');
      // Workers in reserve
      if (meeple.location == 'reserve') {
        if (meeple.type == 'worker') {
          return $(`reserve-${meeple.pId}-worker`);
        } else {
          return $('box-reserve');
        }
      }
      // Coal on pit tile
      if (t[0] == 'pit' && t[1] == 'tile') {
        let side = t[3] == '-1' ? 'left' : 'right';
        return $(`${side}-pit-${meeple.pId}-${t[2]}`).querySelector('.pit-tile');
      }
      // Worker on board
      if (meeple.type == 'worker' && $(meeple.location)) {
        return $(meeple.location).querySelector('.space-workers-container');
      }
      // Coal on tile
      if (t[0] == 'tile') {
        return $(`tile-${t[1]}`);
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    notif_moveCoalToTile(n) {
      debug('Notif: putting coal on tile', n);
      this.slide(`meeple-${n.args.coal.id}`, this.getMeepleContainer(n.args.coal));
    },

    ////////////////////////////////////////////////////////
    //    ____              _
    //   / ___|__ _ _ __ __| |___
    //  | |   / _` | '__/ _` / __|
    //  | |__| (_| | | | (_| \__ \
    //   \____\__,_|_|  \__,_|___/
    //////////////////////////////////////////////////////////

    setupCards() {
      // This function is refreshUI compatible
      let cardIds = this.gamedatas.cards.map((card) => {
        if (!$(`card-${card.id}`)) {
          this.addCard(card);
        }

        let o = $(`card-${card.id}`);
        if (!o) return null;

        let container = this.getCardContainer(card);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = card.state;

        return card.id;
      });
      document.querySelectorAll('.coalbaron-card[id^="card-"]').forEach((oCard) => {
        if (!cardIds.includes(parseInt(oCard.getAttribute('data-id')))) {
          this.destroy(oCard);
        }
      });
    },

    addCard(card, location = null) {
      if ($('card-' + card.id)) return;

      let o = this.place('tplCard', card, location == null ? this.getCardContainer(card) : location);
      let tooltipDesc = this.getCardTooltip(card);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getCardTooltip(card) {
      return null;
    },

    tplCard(card) {
      return `<div class="coalbaron-card" id="card-${card.id}" data-id="${card.id}" data-type="${card.type}"></div>`;
    },

    getCardContainer(card) {
      let t = card.location.split('_');
      if (t[0] == 'factory') {
        return $(card.location).querySelector('.space-card-container');
      }

      console.error('Trying to get container of a card', card);
      return 'game_play_area';
    },

    ////////////////////////////////////////////////////////////
    // _____                          _   _   _
    // |  ___|__  _ __ _ __ ___   __ _| |_| |_(_)_ __   __ _
    // | |_ / _ \| '__| '_ ` _ \ / _` | __| __| | '_ \ / _` |
    // |  _| (_) | |  | | | | | | (_| | |_| |_| | | | | (_| |
    // |_|  \___/|_|  |_| |_| |_|\__,_|\__|\__|_|_| |_|\__, |
    //                                                 |___/
    ////////////////////////////////////////////////////////////

    /**
     * Replace some expressions by corresponding html formating
     */
    formatIcon(name, n = null, lowerCase = true) {
      let type = lowerCase ? name.toLowerCase() : name;
      const NO_TEXT_ICONS = [];
      let noText = NO_TEXT_ICONS.includes(name);
      let text = n == null ? '' : `<span>${n}</span>`;
      return `${noText ? text : ''}<div class="icon-container icon-container-${type}">
            <div class="coalbaron-icon icon-${type}">${noText ? '' : text}</div>
          </div>`;
    },

    formatString(str) {
      const ICONS = ['WORKER'];

      ICONS.forEach((name) => {
        // WITHOUT BONUS / WITH TEXT
        const regex = new RegExp('<' + name + ':([^>]+)>', 'g');
        str = str.replaceAll(regex, this.formatIcon(name, '$1'));
        // WITHOUT TEXT
        str = str.replaceAll(new RegExp('<' + name + '>', 'g'), this.formatIcon(name));
      });
      str = str.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
      str = str.replace(/\*\*([^\*]+)\*\*/g, '<b>$1</b>');

      return str;
    },

    /**
     * Format log strings
     *  @Override
     */
    format_string_recursive(log, args) {
      try {
        if (log && args && !args.processed) {
          args.processed = true;

          log = this.formatString(_(log));

          // if (args.amount_money !== undefined) {
          //   args.amount_money = this.formatIcon('money', args.amount_money);
          // }
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return this.inherited(arguments);
    },

    ////////////////////////////////////////////////////////
    //  ___        __         ____                  _
    // |_ _|_ __  / _| ___   |  _ \ __ _ _ __   ___| |
    //  | || '_ \| |_ / _ \  | |_) / _` | '_ \ / _ \ |
    //  | || | | |  _| (_) | |  __/ (_| | | | |  __/ |
    // |___|_| |_|_|  \___/  |_|   \__,_|_| |_|\___|_|
    ////////////////////////////////////////////////////////

    setupInfoPanel() {
      dojo.place(this.tplConfigPlayerBoard(), 'player_boards', 'first');

      let chk = $('help-mode-chk');
      dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
      this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));

      this._settingsModal = new customgame.modal('showSettings', {
        class: 'coalbaron_popin',
        closeIcon: 'fa-times',
        title: _('Settings'),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contentsTpl: `<div id='coalbaron-settings'>
           <div id='coalbaron-settings-header'></div>
           <div id="settings-controls-container"></div>
         </div>`,
      });
    },

    tplConfigPlayerBoard() {
      return `
 <div class='player-board' id="player_board_config">
   <div id="player_config" class="player_board_content">

     <div class="player_config_row" id="round-counter-wrapper">
       ${_('Round')} <span id='round-counter'></span> / <span id='round-counter-total'></span>
     </div>
     <div class="player_config_row" id="round-phase"></div>
     <div class="player_config_row">
       <div id="help-mode-switch">
         <input type="checkbox" class="checkbox" id="help-mode-chk" />
         <label class="label" for="help-mode-chk">
           <div class="ball"></div>
         </label>

         <svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
       </div>

       <div id="show-settings">
         <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
           <g>
             <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
             <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
           </g>
         </svg>
       </div>
     </div>
     <div class="player_config_row" id="pending-gods"></div>
   </div>
 </div>
 `;
    },

    updatePlayerOrdering() {
      this.inherited(arguments);
      dojo.place('player_board_config', 'player_boards', 'first');
    },

    updateTurn() {
      // $('game_play_area').dataset.step = this.gamedatas.turn;
      // let round = parseInt(this.gamedatas.turn / 4) + 1;
      // if (round > 4) round = 4;
      // let turn = this.gamedatas.turn % 4;
      // if (this.prefs && this.prefs[105].value == 1) {
      //   this._roundCounter.toValue(this.gamedatas.turn);
      //   $('round-counter-total').innerHTML = 16;
      //   $('round-phase').innerHTML = turn == 0 ? _('Scoring phase') : '';
      // } else {
      //   $('round-counter-total').innerHTML = 4;
      //   this._roundCounter.toValue(round);
      //   let msgs = {
      //     0: _('Scoring phase'),
      //     1: _('First turn'),
      //     2: _('Second turn'),
      //     3: _('Third turn'),
      //   };
      //   $('round-phase').innerHTML = msgs[turn];
      // }
    },

    notif_newTurn(n) {
      debug('Notif: starting a new turn', n);
      this.gamedatas.turn = n.args.step;
      this.updateTurn();
    },

    notif_newTurnScoring(n) {
      debug('Notif: starting a new turn', n);
      this.notif_newTurn(n);
    },
  });
});
