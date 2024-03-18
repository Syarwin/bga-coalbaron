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
      this._inactiveStates = ['draft'];
      this._notifications = [
        ['clearTurn', 200],
        ['refreshUI', 200],
        ['newShift', null],
        ['updateFirstPlayer', 1000],
        ['placeWorkers', null],
        ['moveToCanteen', null],
        ['giveMoney', 1300],
        ['spendMoney', 1300],
        ['giveTileTo', 1200],
        ['giveCardTo', 1200],
        ['endDraft', 1200],
        ['refillOrderSpace', 1200],
        ['refillFactorySpace', 1200],
        ['returnTiles', 1400],
        ['returnCards', 1400],
        ['movePitCage', 800],
        ['moveCoal', 900],
        ['cardDelivered', null],
        ['startShiftScoring', null],
        ['startMajorityScoring', 700],
        ['endMajorityScoring', 700],
        ['endShiftMajority', 1300],
        ['endShiftScoring', 1200],

        ['endGameScoring', 1200],
        ['endGameScoringMoney', 1200],
        ['endGameScoringCoals', 1200],
        ['endGameScoringCards', 1200],
        ['endGameScoringBalance', 1200],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=800';
    },

    getSettingsConfig() {
      return {
        confirmMode: { type: 'pref', prefId: 103 },
        boardWidth: {
          default: 90,
          name: _('Board width'),
          type: 'slider',
          sliderConfig: {
            step: 3,
            padding: 0,
            range: {
              min: [30],
              max: [100],
            },
          },
        },
        playerBoardWidth: {
          default: 90,
          name: _('Player board scale'),
          type: 'slider',
          sliderConfig: {
            step: 3,
            padding: 0,
            range: {
              min: [30],
              max: [100],
            },
          },
        },
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
      this.setupCards();
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

      // Undo last steps
      if (args.args && args.args.previousSteps) {
        args.args.previousSteps.forEach((stepId) => {
          let logEntry = $('logs').querySelector(`.log.notif_newUndoableStep[data-step="${stepId}"]`);
          if (logEntry) this.onClick(logEntry, () => this.undoToStep(stepId));

          logEntry = document.querySelector(`.chatwindowlogs_zone .log.notif_newUndoableStep[data-step="${stepId}"]`);
          if (logEntry) this.onClick(logEntry, () => this.undoToStep(stepId));
        });
      }

      // Restart turn button
      if (args.args && args.args.previousChoices && args.args.previousChoices >= 1 && !args.args.automaticAction) {
        if (args.args && args.args.previousSteps) {
          let lastStep = Math.max(...args.args.previousSteps);
          if (lastStep > 0)
            this.addDangerActionButton('btnUndoLastStep', _('Undo last step'), () => this.undoToStep(lastStep), 'restartAction');
        }

        // Restart whole turn
        this.addDangerActionButton(
          'btnRestartTurn',
          _('Restart turn'),
          () => {
            this.stopActionTimer();
            this.takeAction('actRestart');
          },
          'restartAction'
        );
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    onAddingNewUndoableStepToLog(notif) {
      if (!$(`log_${notif.logId}`)) return;
      let stepId = notif.msg.args.stepId;
      $(`log_${notif.logId}`).dataset.step = stepId;
      if ($(`dockedlog_${notif.mobileLogId}`)) $(`dockedlog_${notif.mobileLogId}`).dataset.step = stepId;

      if (
        this.gamedatas &&
        this.gamedatas.gamestate &&
        this.gamedatas.gamestate.args &&
        this.gamedatas.gamestate.args.previousSteps &&
        this.gamedatas.gamestate.args.previousSteps.includes(parseInt(stepId))
      ) {
        this.onClick($(`log_${notif.logId}`), () => this.undoToStep(stepId));

        if ($(`dockedlog_${notif.mobileLogId}`)) this.onClick($(`dockedlog_${notif.mobileLogId}`), () => this.undoToStep(stepId));
      }
    },

    onEnteringStateConfirmTurn(args) {
      this.addPrimaryActionButton('btnConfirmTurn', _('Confirm'), () => {
        this.stopActionTimer();
        this.takeAction('actConfirmTurn');
      });

      const OPTION_CONFIRM = 103;
      let n = args.previousChoices;
      let timer = Math.min(10 + 2 * n, 20);
      this.startActionTimer('btnConfirmTurn', timer, this.prefs[OPTION_CONFIRM].value);
    },

    undoToStep(stepId) {
      this.stopActionTimer();
      this.checkAction('actRestart');
      this.takeAction('actUndoToStep', { stepId }, false);
    },

    notif_clearTurn(n) {
      debug('Notif: restarting turn', n);
      this.cancelLogs(n.args.notifIds);
    },

    notif_refreshUI(n) {
      debug('Notif: refreshing UI', n);
      ['meeples', 'players', 'cards', 'tiles'].forEach((value) => {
        this.gamedatas[value] = n.args.datas[value];
      });

      this.setupCards();
      this.setupMeeples();
      this.setupTiles();

      this.forEachPlayer((player) => {
        let pId = player.id;
        this.scoreCtrl[pId].toValue(player.score);
        this._counters[pId].worker.toValue(player.workers);
        this._counters[pId].money.toValue(player.money);

        $(`elevator-${player.id}`).dataset.y = player.cageLevel;
      });
    },

    onEnteringStatePlaceWorker(args) {
      let selectedSpace = null;
      Object.keys(args.spaces).forEach((spaceCategory) => {
        args.spaces[spaceCategory].forEach((spaceId) => {
          this.onClick(spaceId, () => {
            if (selectedSpace !== null) $(selectedSpace).classList.remove('selected');
            selectedSpace = spaceId;
            $(selectedSpace).classList.add('selected');
            this.addPrimaryActionButton('btnConfirm', '', () => this.takeAction('actPlaceWorker', { spaceId: selectedSpace }));
            $('btnConfirm').innerHTML = this.fsr(_('Confirm and place ${n} worker(s)'), { n: args.nbrWorkersNeeded[spaceId] });
          });
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

    notif_moveToCanteen(n) {
      debug('Notif: move to canteen', n);
      Promise.all(
        [...$(n.args.space).querySelectorAll('.coalbaron-meeple')].map((oWorker, i) =>
          this.wait(200 * i).then(() => this.slide(oWorker, $('canteen').querySelector('.space-workers-container')))
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
        this.place('tplPlayerBoard', player, 'coalbaron-main-container');

        let pId = player.id;
        this._counters[pId] = {
          worker: this.createCounter(`counter-${pId}-worker`, player.workers),
          money: this.createCounter(`counter-${pId}-money`, player.money),
        };

        // Useful to order boards
        nPlayers++;
        if (isCurrent) currentPlayerNo = player.no;
        else {
          if (this.gamedatas.optionCardsVisibility == 0) {
            for (let i = 0; i < player.ordersDone; i++) {
              $(`completed-orders-${player.id}`).insertAdjacentHTML(`beforeend`, `<div class="coalbaron-card back-card"></div>`);
            }
          }
        }
      });

      // Order them
      this.forEachPlayer((player) => {
        let order = ((player.no - currentPlayerNo + nPlayers) % nPlayers) + 1;
        $(`board-${player.id}`).style.order = order;

        if (player.id == this.gamedatas.firstPlayer) {
          $(`overall_player_board_${player.id}`)
            .querySelector('.first-player-holder')
            .insertAdjacentHTML('beforeend', '<div id="coalbaron-first-player"></div>');
          this.addCustomTooltip('coalbaron-first-player', _('First player'));
        }
      });

      this.updateFirstPlayer();
    },

    updateFirstPlayer() {
      let pId = this.gamedatas.firstPlayer;
      let container = $(`overall_player_board_${pId}`);
      this.slide('coalbaron-first-player', container.querySelector('.first-player-holder'), {
        phantom: false,
      });
    },

    getPlayerColor(pId) {
      return this.gamedatas.players[pId].color;
    },

    notif_updateFirstPlayer(n) {
      debug('Notif: updating first player', n);
      this.gamedatas.firstPlayer = n.args.player_id;
      this.updateFirstPlayer();
    },

    tplPlayerBoard(player) {
      return `<div class='coalbaron-board' id='board-${player.id}' data-color='${player.color}'>
        <div class='player-board-fixed-size'>
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
            <div class='player-name' style='color:#${player.color}'>${player.name}</div>
            <div class='elevator' id='elevator-${player.id}' data-y="${player.cageLevel}"></div>
            <div class='elevator-level level-0' id='elevator-${player.id}-level-0'></div>
            <div class='elevator-level level-1' id='elevator-${player.id}-level-1'></div>
            <div class='elevator-level level-2' id='elevator-${player.id}-level-2'></div>
            <div class='elevator-level level-3' id='elevator-${player.id}-level-3'></div>
            <div class='elevator-level level-4' id='elevator-${player.id}-level-4'></div>
            <div class='storage' id='storage-${player.id}'></div>
            <div class='joker-double-cubes' id='joker-${player.id}'></div>
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
          from: targetSource || this.getVisibleTitleContainer(),
          destroy: true,
          phantom: false,
          duration: 1200,
        }).then(() => this._counters[pId]['money'].incValue(n));
      } else {
        this._counters[pId]['money'].incValue(n);
        return this.slide('money-animation', targetSource || this.getVisibleTitleContainer(), {
          from: `counter-${pId}-money`,
          destroy: true,
          phantom: false,
          duration: 1200,
        });
      }
    },

    gainLoseScore(pId, n, targetSource = null) {
      if (this.isFastMode()) {
        this.scoreCtrl[pId].incValue(n);
        return Promise.resolve();
      }

      let elem = `<div id='score-animation'>
        ${Math.abs(n)}
        <i class="fa fa-star"></i>
      </div>`;
      $('page-content').insertAdjacentHTML('beforeend', elem);

      // Score animation
      if (n > 0) {
        return this.slide('score-animation', `player_score_${pId}`, {
          from: targetSource || this.getVisibleTitleContainer(),
          destroy: true,
          phantom: false,
          duration: 1100,
        }).then(() => this.scoreCtrl[pId].incValue(n));
      } else {
        this.scoreCtrl[pId].incValue(n);
        return this.slide('score-animation', targetSource || this.getVisibleTitleContainer(), {
          from: `player_score_${pId}`,
          destroy: true,
          phantom: false,
          duration: 1100,
        });
      }
    },

    notif_spendMoney(n) {
      debug('Notif: spending money', n);
      this.gainPayMoney(n.args.player_id, -n.args.n);
    },

    notif_giveMoney(n) {
      debug('Notif: gaining money', n);
      this.gainPayMoney(n.args.player_id, n.args.n);
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
      if (!$(`tile-${n.args.tile.id}`)) this.addTile(n.args.tile, this.getVisibleTitleContainer());
      $(`tile-${n.args.tile.id}`).classList.remove('selected');
      this.slide(`tile-${n.args.tile.id}`, this.getTileContainer(n.args.tile));
    },

    notif_refillFactorySpace(n) {
      debug('Notif: refill tunnel tiles', n);
      this.addTile(n.args.tile, this.getVisibleTitleContainer());
      this.slide(`tile-${n.args.tile.id}`, this.getTileContainer(n.args.tile));
    },

    onEnteringStateChooseTile(args) {
      [...$('draft-container').querySelectorAll('.coalbaron-tile[data-order]')].forEach((o) => delete o.dataset.order);

      let tiles = args._private.tiles;
      Object.values(tiles).forEach((tile) => {
        this.addTile(tile, $('draft-container'));
        if (!tile.buyable) return;

        this.onClick(`tile-${tile.id}`, () =>
          this.clientState('chooseTileRemaining', _('Select where and in which order you want to place the other tiles'), {
            tileId: tile.id,
            tiles,
          })
        );
      });

      this.addSecondaryActionButton('btnNoTake', _("Don't take any tile"), () => {
        this.clientState('chooseTileRemaining', _('Select where and in which order you want to place the tiles'), {
          tiles,
        });
      });
    },

    onEnteringStateChooseTileRemaining(args) {
      this.addCancelStateBtn();
      let tileId = args.tileId ? args.tileId : null;
      if (tileId !== null) {
        $(`tile-${tileId}`).classList.add('selected');
      }

      let selectedPosition = null;
      let orderIds = [];

      let updateStatus = () => {
        if (selectedPosition !== null && orderIds.length == (tileId === null ? 5 : 4)) {
          this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
            this.takeAction('actChooseTile', { tileId, returnDest: selectedPosition, otherIds: orderIds.join(';') })
          );
        } else if ($('btnConfirm')) {
          $('btnConfirm').remove();
        }

        if (selectedPosition !== null) {
          $('btnTOP').classList.toggle('selected', selectedPosition == 'TOP');
          $('btnBOTTOM').classList.toggle('selected', selectedPosition == 'BOTTOM');
        }

        if (orderIds.length > 0) {
          this.addSecondaryActionButton('btnCancelOrder', _('Reset tile order'), () => {
            orderIds = [];
            [...$('draft-container').querySelectorAll('.coalbaron-tile[data-order]')].forEach((o) => delete o.dataset.order);
            updateStatus();
          });
        } else if ($('btnCancelOrder')) {
          $('btnCancelOrder').remove();
        }
      };

      // Choose position
      this.addPrimaryActionButton('btnTOP', _('Place on top (1 = top tile)'), () => {
        selectedPosition = 'TOP';
        updateStatus();
      });
      this.addPrimaryActionButton('btnBOTTOM', _('Place on bottom (1 = bottom tile)'), () => {
        selectedPosition = 'BOTTOM';
        updateStatus();
      });

      // Choose order
      Object.values(args.tiles).forEach((tile) => {
        if (tile.id === tileId) return;

        this.onClick(`tile-${tile.id}`, () => {
          if (orderIds.includes(tile.id)) return;

          orderIds.push(tile.id);
          $(`tile-${tile.id}`).dataset.order = orderIds.length;
          updateStatus();
        });
      });
    },

    notif_returnTiles(n) {
      debug('Notif: return tiles', n);
      if (n.args.player_id != this.player_id) return;

      for (let i = 1; i <= n.args.n; i++) {
        let oTile = $('draft-container').querySelector(`.coalbaron-tile[data-order='${i}']`);
        this.wait((i - 1) * 200).then(() => this.slide(oTile, this.getVisibleTitleContainer(), { destroy: true }));
      }
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
      let color = PERSONAL.includes(type) ? ` data-color="${this.getPlayerColor(meeple.pId)}" data-pId="${meeple.pId}" ` : '';
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
      // Coal on pit cage
      if (meeple.location == 'pit_cage') {
        return $(`elevator-${meeple.pId}`);
      }
      // Coal in storage
      if (meeple.location == 'storage') {
        return $(`storage-${meeple.pId}`);
      }
      // Coal on card
      if (t[0] == 'card') {
        return $(`coal-slot-${t[1]}-${t[2]}`);
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
      let slots = '';
      let nCoals = card.coals.length;
      for (let i = 0; i < nCoals; i++) {
        slots += `<div class='coal-slot' id='coal-slot-${card.id}-${i}'></div>`;
      }

      return `<div class="coalbaron-card" id="card-${card.id}" data-id="${card.id}" data-type="${card.type}" data-n="${nCoals}">
        ${slots}
      </div>`;
    },

    getCardContainer(card) {
      let t = card.location.split('_');
      if (t[0] == 'order') {
        return $(card.location).querySelector('.space-order-container');
      }
      if (card.location == 'outstanding') {
        return $(`pending-orders-${card.pId}`);
      }
      if (card.location == 'delivered') {
        return $(`completed-orders-${card.pId}`);
      }

      console.error('Trying to get container of a card', card);
      return 'game_play_area';
    },

    onEnteringStateDraft(args) {
      let selectedCard = null;
      Object.values(args.cards).forEach((card) => {
        this.addCard(card, $('draft-container'));
        if (this.isCurrentPlayerActive()) {
          this.onClick(`card-${card.id}`, () => {
            if (selectedCard) $(`card-${selectedCard}`).classList.remove('selected');
            selectedCard = card.id;
            $(`card-${selectedCard}`).classList.add('selected');
            this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
              this.takeAction('actTakeCard', { cardId: selectedCard })
            );
          });
        }
      });
    },

    notif_giveCardTo(n) {
      debug('Notif: receiving a new order cards', n);
      if (!$(`card-${n.args.card.id}`)) this.addCard(n.args.card, this.getVisibleTitleContainer());
      $(`card-${n.args.card.id}`).classList.remove('selected');
      this.slide(`card-${n.args.card.id}`, `pending-orders-${n.args.player_id}`);
    },

    notif_endDraft(n) {
      debug('Notif: end of draft', n);
      this.slide(`card-${n.args.card.id}`, this.getCardContainer(n.args.card));
    },

    notif_refillOrderSpace(n) {
      debug('Notif: refill order cards', n);
      this.addCard(n.args.card, this.getVisibleTitleContainer());
      this.slide(`card-${n.args.card.id}`, this.getCardContainer(n.args.card));
    },

    onEnteringStateChooseCard(args) {
      [...$('draft-container').querySelectorAll('.coalbaron-card[data-order]')].forEach((o) => delete o.dataset.order);

      let cards = args._private.cards;
      Object.values(cards).forEach((card) => {
        this.addCard(card, $('draft-container'));
        this.onClick(`card-${card.id}`, () =>
          this.clientState('chooseCardRemaining', _('Select where and in which order you want to place the other cards'), {
            cardId: card.id,
            cards,
          })
        );
      });

      this.addSecondaryActionButton('btnNoTake', _("Don't take any card"), () => {
        this.clientState('chooseCardRemaining', _('Select where and in which order you want to place the cards'), {
          cards,
        });
      });
    },

    onEnteringStateChooseCardRemaining(args) {
      this.addCancelStateBtn();
      let cardId = args.cardId ? args.cardId : null;
      if (cardId !== null) {
        $(`card-${cardId}`).classList.add('selected');
      }

      let selectedPosition = null;
      let orderIds = [];

      let updateStatus = () => {
        if (selectedPosition !== null && orderIds.length == (cardId === null ? 5 : 4)) {
          this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
            this.takeAction('actChooseCard', { cardId, returnDest: selectedPosition, otherIds: orderIds.join(';') })
          );
        } else if ($('btnConfirm')) {
          $('btnConfirm').remove();
        }

        if (selectedPosition !== null) {
          $('btnTOP').classList.toggle('selected', selectedPosition == 'TOP');
          $('btnBOTTOM').classList.toggle('selected', selectedPosition == 'BOTTOM');
        }

        if (orderIds.length > 0) {
          this.addSecondaryActionButton('btnCancelOrder', _('Reset card order'), () => {
            orderIds = [];
            [...$('draft-container').querySelectorAll('.coalbaron-card[data-order]')].forEach((o) => delete o.dataset.order);
            updateStatus();
          });
        } else if ($('btnCancelOrder')) {
          $('btnCancelOrder').remove();
        }
      };

      // Choose position
      this.addPrimaryActionButton('btnTOP', _('Place on top (1 = top card)'), () => {
        selectedPosition = 'TOP';
        updateStatus();
      });
      this.addPrimaryActionButton('btnBOTTOM', _('Place on bottom (1 = bottom card)'), () => {
        selectedPosition = 'BOTTOM';
        updateStatus();
      });

      // Choose order
      Object.values(args.cards).forEach((card) => {
        if (card.id === cardId) return;

        this.onClick(`card-${card.id}`, () => {
          if (orderIds.includes(card.id)) return;

          orderIds.push(card.id);
          $(`card-${card.id}`).dataset.order = orderIds.length;
          updateStatus();
        });
      });
    },

    notif_returnCards(n) {
      debug('Notif: return cards', n);
      if (n.args.player_id != this.player_id) return;

      for (let i = 1; i <= n.args.n; i++) {
        let oCard = $('draft-container').querySelector(`.coalbaron-card[data-order='${i}']`);
        this.wait((i - 1) * 200).then(() => this.slide(oCard, this.getVisibleTitleContainer(), { destroy: true }));
      }
    },

    onEnteringStateMiningSteps(args) {
      args.movableCage.forEach((level) => {
        this.onClick(`elevator-${this.player_id}-level-${level}`, () => this.takeAction('actMovePitCage', { level }));
      });

      let movableToDuo = [];
      let allLocations = [];
      Object.keys(args.movableCoals.solo).forEach((meepleId) => {
        let locations = args.movableCoals.solo[meepleId];
        if (locations.length == 0) return;
        locations.forEach((loc) => {
          if (loc == 'duo') {
            movableToDuo.push(meepleId);
          }

          if (allLocations[loc]) allLocations[loc].push(meepleId);
          else allLocations[loc] = [meepleId];
        });

        this.onClick(`meeple-${meepleId}`, () => {
          if (locations.length == 1) {
            this.takeAction('actMoveCoals', { spaceId: locations[0], coalId: meepleId });
          } else {
            this.clientState('miningStepsChooseTarget', _('Where do you want to move that coal?'), {
              meepleId,
              movableCoals: args.movableCoals,
            });
          }
        });
      });

      Object.keys(allLocations).forEach((location) => {
        let t = location.split('_');
        if (t[0] == 'card') {
          let meepleId = allLocations[location][0]; // TODO : something smarter
          this.onClick(`coal-slot-${t[1]}-${t[2]}`, () => {
            this.takeAction('actMoveCoals', { spaceId: location, coalId: meepleId });
          });
        }
      });

      // At least two meeple movable to duo
      if (movableToDuo.length >= 2) {
        this.onClick(`joker-${this.player_id}`, () => {
          this.clientState('miningStepsChooseDuo', _('Choose two coals'), {
            meepleIds: movableToDuo,
            locations: args.movableCoals.duo,
          });
        });
      }

      this.addDangerActionButton('btnStopMining', _('Stop mining'), () => this.takeAction('actStopMining', {}));
    },

    onEnteringStateMiningStepsChooseTarget(args) {
      this.addCancelStateBtn();
      let meepleId = args.meepleId;
      $(`meeple-${meepleId}`).classList.add('selected');
      this.onClick(`meeple-${meepleId}`, () => this.clearClientState());

      let locations = args.movableCoals.solo[meepleId];
      locations.forEach((location) => {
        let t = location.split('_');
        // Card
        if (t[0] == 'card') {
          this.onClick(`coal-slot-${t[1]}-${t[2]}`, () => {
            this.takeAction('actMoveCoals', { spaceId: location, coalId: meepleId });
          });
        }
        // Storage
        if (location == 'storage') {
          this.onClick(`storage-${this.player_id}`, () => {
            this.takeAction('actMoveCoals', { spaceId: location, coalId: meepleId });
          });
        }
      });
    },

    onEnteringStateMiningStepsChooseDuo(args) {
      this.addCancelStateBtn(_('Go back'));
      let elements = [];
      args.meepleIds.forEach((meepleId) => (elements[meepleId] = $(`meeple-${meepleId}`)));
      this.onSelectN(elements, 2, (selectedMeeples) => {
        this.clientState('miningStepsChooseDuoTarget', _('Where do you want to move these coals?'), {
          selectedMeeples,
          locations: args.locations,
        });
      });
    },
    onEnteringStateMiningStepsChooseDuoTarget(args) {
      this.addCancelStateBtn(_('Go back'));
      let meepleId1 = args.selectedMeeples[0];
      let meepleId2 = args.selectedMeeples[1];
      $(`meeple-${meepleId1}`).classList.add('selected');
      $(`meeple-${meepleId2}`).classList.add('selected');

      args.locations.forEach((location) => {
        let t = location.split('_');
        if (t[0] == 'card') {
          this.onClick(`coal-slot-${t[1]}-${t[2]}`, () => {
            this.takeAction('actMoveCoals', { spaceId: location, coalId: args.selectedMeeples.join(';') });
          });
        }
      });
    },

    notif_movePitCage(n) {
      debug('Notif: move pit cage', n);
      $(`elevator-${n.args.player_id}`).dataset.y = n.args.b;
    },

    notif_moveCoal(n) {
      debug('Notif: moving coald', n);
      $(`meeple-${n.args.coal.id}`).classList.remove('selected');
      $(`meeple-${n.args.coal.id}`).classList.remove('selectable');
      this.slide(`meeple-${n.args.coal.id}`, this.getMeepleContainer(n.args.coal));
    },

    notif_cardDelivered(n) {
      debug('Notif: card delivered', n);
      Promise.all(
        [...$(`card-${n.args.cardId}`).querySelectorAll('.coal-slot .coalbaron-meeple')].map((oMeeple, i) =>
          this.slide(oMeeple, this.getVisibleTitleContainer(), { destroy: true, delay: 100 * i })
        )
      ).then(() => {
        this.scoreCtrl[n.args.player_id].incValue(n.args.n);
        let oCard = $(`card-${n.args.cardId}`);
        this.slide(oCard, `completed-orders-${n.args.player_id}`).then(() => {
          if (this.player_id != n.args.player_id && this.gamedatas.optionCardsVisibility == 0) {
            this.flipAndReplace(oCard, `<div class="coalbaron-card back-card"></div>`);
          }

          this.notifqueue.setSynchronousDuration(100);
        });
      });
    },

    notif_startShiftScoring(n) {
      debug('Notif: reveal hidden cards for scoring', n);
      Promise.all(
        n.args.cards.map((card, i) => {
          if ($(`card-${card.id}`) || this.gamedatas.optionCardsVisibility == 1) return;

          let pId = card.pId;
          let oCard = $(`completed-orders-${pId}`).querySelector('.coalbaron-card.back-card:not(.revealing)');
          oCard.classList.add('revealing');
          return this.wait(100 * i).then(() => this.flipAndReplace(oCard, this.tplCard(card)));
        })
      ).then(() => {
        this._scoresheetModal.show();
        $(`scoring-shift-${n.args.shift}`).classList.add('active');
        this.notifqueue.setSynchronousDuration(100);
      });
    },

    notif_endShiftScoring(n) {
      debug('Notif: end of shift scoring', n);
      $(`scoring-shift-${n.args.shift}`).classList.remove('active');

      if (this.gamedatas.optionCardsVisibility == 0) {
        n.args.cards.forEach((card) => {
          let pId = card.pId;
          if (pId == this.player_id) return;

          let oCard = $(`card-${card.id}`);
          this.flipAndReplace(oCard, `<div class="coalbaron-card back-card"></div>`);
        });
      }

      this.wait(700).then(() => this._scoresheetModal.hide());
    },

    notif_startMajorityScoring(n) {
      debug('Notif: start scoring a majority', n);
      $(`scoring-cell-${n.args.shift}-${n.args.i}`).classList.add('active');
      this.gamedatas.shift = n.args.shift;
    },

    notif_endShiftMajority(n) {
      debug('Notif: scoring a majority', n);
      let pId = n.args.player_id;

      // Add meeple on the scoreboard
      let pos = n.args.pos == 1 ? 'first' : 'second';
      $(`scoring-cell-${this.gamedatas.shift}-${n.args.i}`)
        .querySelector(`.scoring-cell-${pos}`)
        .insertAdjacentHTML(
          'beforeend',
          `<div class='marker' style='background-color:#${this.gamedatas.players[pId].color}'></div>`
        );

      // Score animation
      let score = n.args.p;
      let elem = `<div id='score-animation'>
        ${score}
        <i class="fa fa-star" id="icon_point_2322021"></i>
      </div>`;
      $(`scoring-board-${pos}-${n.args.i}`).insertAdjacentHTML('beforeend', elem);

      this.slide('score-animation', `player_score_${pId}`, {
        destroy: true,
        phantom: false,
        duration: 1200,
      }).then(() => this.scoreCtrl[pId].incValue(score));
    },

    notif_endMajorityScoring(n) {
      debug('Notif: end scoring a majority', n);
      $(`scoring-cell-${n.args.shift}-${n.args.i}`).classList.remove('active');
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

      this._scoresheetModal = new customgame.modal('showScoresheet', {
        class: 'coalbaron_popin',
        closeIcon: 'fa-times',
        title: _('Scoring board'),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contentsTpl: `<div id='scoring-board'>
          <div id='scoring-board-first'>
            <div id='scoring-board-first-1'></div>
            <div id='scoring-board-first-2'></div>
            <div id='scoring-board-first-3'></div>
            <div id='scoring-board-first-4'></div>
            <div id='scoring-board-first-5'></div>
            <div id='scoring-board-first-6'></div>
            <div id='scoring-board-first-7'></div>
            <div id='scoring-board-first-8'></div>
            <div id='scoring-board-first-9'></div>
            <div id='scoring-board-first-10'></div>
            <div id='scoring-board-first-11'></div>
            <div id='scoring-board-first-12'></div>
          </div>
          <div id='scoring-board-second'>
            <div id='scoring-board-second-1'></div>
            <div id='scoring-board-second-2'></div>
            <div id='scoring-board-second-3'></div>
            <div id='scoring-board-second-4'></div>
            <div id='scoring-board-second-5'></div>
            <div id='scoring-board-second-6'></div>
            <div id='scoring-board-second-7'></div>
            <div id='scoring-board-second-8'></div>
            <div id='scoring-board-second-9'></div>
            <div id='scoring-board-second-10'></div>
            <div id='scoring-board-second-11'></div>
            <div id='scoring-board-second-12'></div>
          </div>
          <div class='scoring-shift' id='scoring-shift-1'>
            <div class='scoring-shift-header'></div>
          </div>
          <div class='scoring-shift' id='scoring-shift-2'>
            <div class='scoring-shift-header'></div>
          </div>
          <div class='scoring-shift' id='scoring-shift-3'>
            <div class='scoring-shift-header'></div>
          </div>
        </div>`,
        breakpoint: 905,
        scale: 0.9,
      });
      $('show-scoresheet').addEventListener('click', () => this._scoresheetModal.show());

      for (let shift = 1; shift <= 3; shift++) {
        for (let i = 1; i <= 4 * shift; i++) {
          $(`scoring-shift-${shift}`).insertAdjacentHTML(
            'beforeend',
            `<div class='scoring-cell' id='scoring-cell-${shift}-${i}'>
            <div class='scoring-cell-first'></div>
            <div class='scoring-cell-second'></div>
          </div>`
          );
        }
      }

      let majorities = this.gamedatas.majorities;
      Object.keys(majorities).forEach((s) => {
        if (majorities[s].length == 0) return;
        let t = s.split('_');

        let i = t[0];
        let pos = t[1] == 1 ? 'first' : 'second';
        let shift = t[2];
        let container = $(`scoring-cell-${shift}-${i}`).querySelector(`.scoring-cell-${pos}`);
        majorities[s].forEach((pId) =>
          container.insertAdjacentHTML(
            'beforeend',
            `<div class='marker' style='background-color:#${this.gamedatas.players[pId].color}'></div>`
          )
        );
      });
    },

    tplConfigPlayerBoard() {
      return `
 <div class='player-board' id="player_board_config">
   <div id="player_config" class="player_board_content">

     <div class="player_config_row" id="round-counter-wrapper">
       ${_('Shift')} <span id='round-counter'></span> / <span id='round-counter-total'>3</span>
     </div>
     <div class="player_config_row">
      <div id="show-scoresheet">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
          <g class="fa-group">
            <path class="fa-secondary" fill="currentColor" d="M0 192v272a48 48 0 0 0 48 48h352a48 48 0 0 0 48-48V192zm324.13 141.91a11.92 11.92 0 0 1-3.53 6.89L281 379.4l9.4 54.6a12 12 0 0 1-17.4 12.6l-49-25.8-48.9 25.8a12 12 0 0 1-17.4-12.6l9.4-54.6-39.6-38.6a12 12 0 0 1 6.6-20.5l54.7-8 24.5-49.6a12 12 0 0 1 21.5 0l24.5 49.6 54.7 8a12 12 0 0 1 10.13 13.61zM304 128h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16zm-192 0h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16z" opacity="0.4"></path>
            <path class="fa-primary" fill="currentColor" d="M314 320.3l-54.7-8-24.5-49.6a12 12 0 0 0-21.5 0l-24.5 49.6-54.7 8a12 12 0 0 0-6.6 20.5l39.6 38.6-9.4 54.6a12 12 0 0 0 17.4 12.6l48.9-25.8 49 25.8a12 12 0 0 0 17.4-12.6l-9.4-54.6 39.6-38.6a12 12 0 0 0-6.6-20.5zM400 64h-48v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H160v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H48a48 48 0 0 0-48 48v80h448v-80a48 48 0 0 0-48-48z"></path>
          </g>
        </svg>
      </div>
      
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
   </div>
 </div>
 `;
    },

    updatePlayerOrdering() {
      this.inherited(arguments);
      dojo.place('player_board_config', 'player_boards', 'first');
    },

    updateTurn() {
      this._roundCounter.toValue(this.gamedatas.shift);
    },

    notif_newShift(n) {
      debug('Notif: starting a new shift', n);
      this.gamedatas.shift = n.args.n;
      this.updateTurn();

      Promise.all(
        [...$('coalbaron-board').querySelectorAll('.icon-worker')].map((oMeeple, i) => {
          return this.slide(oMeeple, $(`reserve-${oMeeple.dataset.pid}-worker`), { delay: 30 * i }).then(() =>
            this._counters[oMeeple.dataset.pid]['worker'].incValue(1)
          );
        })
      ).then(() => {
        this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
      });
    },

    onChangeBoardWidthSetting(val) {
      this.updateLayout();
    },

    onLoadingComplete() {
      this.updateLayout();
      this.inherited(arguments);
    },

    onScreenWidthChange() {
      if (this.settings) this.updateLayout();
    },

    updateLayout() {
      if (!this.settings) return;
      const ROOT = document.documentElement;

      const WIDTH = $('coalbaron-main-container').getBoundingClientRect()['width'];
      const HEIGHT = (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) - 62;
      const BOARD_WIDTH = 1650;
      const BOARD_HEIGHT = 750;

      let widthScale = ((this.settings.boardWidth / 100) * WIDTH) / BOARD_WIDTH,
        heightScale = HEIGHT / BOARD_HEIGHT,
        scale = Math.min(widthScale, heightScale);
      ROOT.style.setProperty('--boardScale', scale);

      const PLAYER_BOARD_WIDTH = 1650;
      const PLAYER_BOARD_HEIGHT = 840;
      widthScale = ((this.settings.playerBoardWidth / 100) * WIDTH) / PLAYER_BOARD_WIDTH;
      heightScale = HEIGHT / PLAYER_BOARD_HEIGHT;
      scale = Math.min(widthScale, heightScale);
      ROOT.style.setProperty('--playerBoardScale', scale);
    },

    notif_endGameScoring(n) {},

    notif_endGameScoringMoney(n) {
      debug('Notif: gaining score from money', n);
      this.gainLoseScore(n.args.player_id, n.args.p);
    },
    notif_endGameScoringCoals(n) {
      debug('Notif: gaining score from coals', n);
      this.gainLoseScore(n.args.player_id, n.args.p);
    },
    notif_endGameScoringCards(n) {
      debug('Notif: gaining score from cards', n);
      this.gainLoseScore(n.args.player_id, n.args.p);
    },
    notif_endGameScoringBalance(n) {
      debug('Notif: losing score from money', n);
      this.gainLoseScore(n.args.player_id, n.args.p);
    },
  });
});
