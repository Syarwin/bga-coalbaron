/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Mathieu Chatrain <EMAIL>
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
  g_gamethemeurl + 'modules/js/biomes.js',
], function (dojo, declare) {
  return declare('bgagame.coalbaron', [customgame.game], {
    constructor() {
      this._activeStates = [];
      this._notifications = [
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

      this.setupCentralBoard();
      this.setupPlayers();
      this.setupInfoPanel();

      // Create round counter
      this._roundCounter = this.createCounter('round-counter');
      this.updateTurn();

      this.inherited(arguments);
    },

    setupCentralBoard() {
      let container = $('coalbaron-board');
      const SPACES = [
        'factory_1',
        'factory_2',
        'factory_3',
        'factory_4',
        'factory_5',
        'factory_6',
        'factory_7',
        'factory_8',
        'factory_draw',
        'mining_4',
        'mining_6',
        'mining_8',
        'mining_10',
        'delivery_1',
        'delivery_2',
        'delivery_3',
        'delivery_4',
        'bank_3',
        'bank_4',
        'bank_5',
        'bank_6',
        'order_1',
        'order_2',
        'order_3',
        'order_4',
        'order_draw',
        'canteen',
      ];

      SPACES.forEach((spaceId) => {
        container.insertAdjacentHTML('beforeend', `<div class='board-space' id='${spaceId}'></div>`);
      });
    },

    setupPlayers() {
      let currentPlayerNo = 1;
      let nPlayers = 0;
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

    notif_updateFirstPlayer(n) {
      debug('Notif: updating first player', n);
      this.gamedatas.firstPlayer = n.args.pId;
      this.updateFirstPlayer();
    },

    tplPlayerBoard(player) {
      let content = '';

      return `<div class='coalbaron-board' id='board-${player.id}' data-color='${player.color}'>
         <div class='player-name' style='color:#${player.color}'>${player.name}</div>
      </div>`;
    },

    /**
     * Player panel
     */

    tplPlayerPanel(player) {
      return `<div class='coalbaron-panel'>
        <div class="first-player-holder"></div>
        <div class='coalbaron-player-infos'></div>
      </div>`;
    },

    // gainPayCrystal(pId, n, targetSource = null) {
    //   if (this.isFastMode()) {
    //     this._crystalCounters[pId].incValue(n);
    //     return Promise.resolve();
    //   }

    //   let elem = `<div id='crystal-animation' class='crystal-icon'>${Math.abs(
    //     n
    //   )}</div>`;
    //   $("page-content").insertAdjacentHTML("beforeend", elem);
    //   if (n > 0) {
    //     return this.slide("crystal-animation", `crystal-counter-${pId}`, {
    //       from: targetSource || "page-title",
    //       destroy: true,
    //       phantom: false,
    //       duration: 1200,
    //     }).then(() => this._crystalCounters[pId].incValue(n));
    //   } else {
    //     this._crystalCounters[pId].incValue(n);
    //     return this.slide("crystal-animation", targetSource || "page-title", {
    //       from: `crystal-counter-${pId}`,
    //       destroy: true,
    //       phantom: false,
    //       duration: 1200,
    //     });
    //   }
    // },

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
