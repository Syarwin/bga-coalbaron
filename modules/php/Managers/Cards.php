<?php

namespace COAL\Managers;

use COAL\Core\Game;
use COAL\Core\Notifications;
use COAL\Helpers\Collection;

/* Class to manage all the god cards for CoalBaron */

class Cards extends \COAL\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type'];

  protected static function cast($row)
  {
    $data = self::getCards()[$row['type']];
    $data['type'] = $row['type'];
    return new \COAL\Models\Card($row, $data);
  }

  public static function getUiData()
  {
    return self::getInLocation(CARD_LOCATION_OUTSTANDING)
      ->merge(self::getInLocation(SPACE_ORDER . '_%'))
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toArray();
  }
  /**
   * @return int number of cards in deck
   */
  public static function getDeckSize()
  {
    return self::countInLocation(CARD_LOCATION_DECK);
  }
  /**
   * Draw $number cards to choose in "draft" phase
   */
  public static function drawCardsToDraft($number)
  {
    return self::pickForLocation($number, CARD_LOCATION_DECK, CARD_LOCATION_DRAFT, 0, false);
  }
  /**
   * return list of all cards to choose in "draft" phase
   */
  public static function getDraft()
  {
    return self::getInLocation(CARD_LOCATION_DRAFT)
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toAssoc();
  }

  /**
   * Draw a new card and place it in the board order $space
   */
  public static function refillOrderSpace($space)
  {
    $newCard = self::pickOneForLocation(CARD_LOCATION_DECK, $space, 0, false);
    return $newCard;
  }
  /**
   * Draw a new card and place it in the board order space except for the specified $spaceX (and the draw space)
   */
  public static function refillOtherOrderSpaces($exceptSpaceX)
  {
    $nbPlayers = Players::count();
    $spaces = self::getUnlockedOrderSpaces($nbPlayers);
    foreach ($spaces as $space) {
      if ($space == SPACE_ORDER_DRAW || $space == $exceptSpaceX) {
        continue;
      }
      $newCard = self::refillOrderSpace($space);
      Notifications::refillOrderSpace($newCard);
    }
  }
  /**
   * Read the card in a specified board space
   */
  public static function getCardInOrder($space)
  {
    $card = self::getTopOf($space);
    return $card;
  }

  /**
   * Move all these ordered cards to the TOP of the deck after checking they are at $fromLocation
   * @param array $cardsIdArray
   * @param string $fromLocation
   * @param string $toLocation
   */
  public static function moveAllToTop($cardsIdArray, $fromLocation, $toLocation)
  {
    $cards = self::getMany($cardsIdArray);
    //Warning! the result of getMany is not ordered like array input !
    //foreach($cards as $cardId => $card){
    foreach ($cardsIdArray as $cardId) {
      $card = $cards[$cardId];
      if ($card->getLocation() != $fromLocation) {
        throw new \BgaVisibleSystemException("Card $cardId is not at the right place ($fromLocation)");
      }
      self::insertOnTop($cardId, $toLocation);
    }
  }

  /**
   * Move all these ordered cards to the BOTTOM of the deck after checking they are at $fromLocation
   * @param array $cardsIdArray
   * @param string $fromLocation
   * @param string $toLocation
   */
  public static function moveAllToBottom($cardsIdArray, $fromLocation, $toLocation)
  {
    $cards = self::getMany($cardsIdArray);
    //Warning! the result of getMany is not ordered like array input !
    //foreach($cards as $cardId => $card){
    foreach ($cardsIdArray as $cardId) {
      $card = $cards[$cardId];
      if ($card->getLocation() != $fromLocation) {
        throw new \BgaVisibleSystemException("Card $cardId is not at the right place ($fromLocation)");
      }
      self::insertAtBottom($cardId, $toLocation);
    }
  }
  /**
   * Move a card to a player outstanding orders
   */
  public static function giveCardTo($player, $card)
  {
    $card->moveToOutstanding($player);
    Notifications::giveCardTo($player, $card);
  }

  /**
   * For one card, return the list of all coal spots with either the coal currently in the spot, either "EMPTY_SPOT"
   */
  public static function getCardCoalsStatus($cardId)
  {
    Game::get()->trace("getCardCoalsStatus($cardId)...");
    $datas = [];
    $card = self::get($cardId);
    $wantedCoals = $card->getCoals();
    $coalIndex = 0;
    foreach ($wantedCoals as $wantedCoal) {
      //loop string list
      $datas[$coalIndex] = [$wantedCoal => COAL_EMPTY_SPOT];
      $coalIndex++;
    }
    $pId = $card->getPId();
    if (!isset($pId)) {
      return $datas;
    }
    $filledCoals = Meeples::getPlayerCardCoals($pId, $cardId);
    foreach ($filledCoals as $filledCoal) {
      //loop CoalCube list
      $coalIndex = $filledCoal->getCoalSpotIndexOnCard();
      $datas[$coalIndex] = [$wantedCoal => $filledCoal];
    }
    return $datas;
  }

  /**
   * Move all coal cubes from this card to the reserve
   */
  public static function removeCoalCubesOnCard($card)
  {
    //moveAllInLocation Doesn't work with like...
    //Meeples::moveAllInLocation(COAL_LOCATION_CARD.$cardId.'%', SPACE_RESERVE);

    $coalIds = [];
    $filledCoals = Meeples::getPlayerCardCoals($card->getPId(), $card->getId());
    foreach ($filledCoals as $coalCube) {
      $coalIds[] = $coalCube->getId();
      $coalCube->setPId(null);
    }
    //update all  $coalIds at once:
    Meeples::move($coalIds, SPACE_RESERVE);
  }
  /**
   * @return int number of ALL CARDS owned by that player and in that $location,
   *   or ALL CARDS owned by that player if location not given
   */
  public static function countPlayerOrders($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  /**
   * Return all pending cards of this player of type $deliveryType
   */
  public static function getPlayerOrders($pId, $deliveryType)
  {
    //! DB type is not deliveryType -> we cannot query it
    $filter = function ($card) use ($deliveryType) {
      if ($card->getTransport() == $deliveryType) {
        return true;
      }
      return false;
    };
    return self::getFilteredQuery($pId, CARD_LOCATION_OUTSTANDING)
      ->get()
      ->filter($filter);
  }
  /**
   * Return all pending cards of this player
   * @param int
   * @return Collection
   */
  public static function getPlayerPendingOrders($pId)
  {
    return self::getFilteredQuery($pId, CARD_LOCATION_OUTSTANDING)
      ->get();
  }
  /**
   * Return the list of this player ($pId) order cards of type $deliveryType, which contain all needed coal cubes
   */
  public static function getPlayerCompletedOrdersToDeliver($pId, $deliveryType)
  {
    $filter = function ($card) {
      if ($card->isCompleted()) {
        return true;
      }
      return false;
    };
    return self::getPlayerOrders($pId, $deliveryType)->filter($filter);
  }

  /**
   * Return all unlocked ORDER spaces on board
   */
  public static function getUnlockedOrderSpaces($nbPlayers)
  {
    $spaces = [SPACE_ORDER_2, SPACE_ORDER_3, SPACE_ORDER_4, SPACE_ORDER_DRAW];
    if ($nbPlayers >= 3) {
      $spaces[] = SPACE_ORDER_1;
    }
    return $spaces;
  }

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getCards() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_DECK,
        'type' => $type,
      ];
    }

    self::create($cards);
    self::shuffle(CARD_LOCATION_DECK);
  }

  public function getCards()
  {
    $f = function ($t) {
      return [
        'transport' => $t[0],
        'points' => $t[1],
        'coals' => $t[2],
      ];
    };
    return [
      // 11 different barrow
      1 => $f([TRANSPORT_BARROW, 2, [YELLOW_COAL]]),
      2 => $f([TRANSPORT_BARROW, 3, [BROWN_COAL]]),
      3 => $f([TRANSPORT_BARROW, 3, [YELLOW_COAL, YELLOW_COAL]]),
      4 => $f([TRANSPORT_BARROW, 4, [GREY_COAL]]),
      5 => $f([TRANSPORT_BARROW, 5, [BLACK_COAL]]),
      6 => $f([TRANSPORT_BARROW, 5, [BROWN_COAL, BROWN_COAL]]),
      7 => $f([TRANSPORT_BARROW, 7, [BROWN_COAL, BROWN_COAL, BROWN_COAL]]),
      8 => $f([TRANSPORT_BARROW, 7, [GREY_COAL, GREY_COAL]]),
      9 => $f([TRANSPORT_BARROW, 9, [BLACK_COAL, BLACK_COAL]]),
      10 => $f([TRANSPORT_BARROW, 9, [GREY_COAL, GREY_COAL, GREY_COAL]]),
      11 => $f([TRANSPORT_BARROW, 10, [YELLOW_COAL, BROWN_COAL, GREY_COAL, BLACK_COAL]]),
      // 11 different motorcar
      12 => $f([TRANSPORT_MOTORCAR, 2, [YELLOW_COAL]]),
      13 => $f([TRANSPORT_MOTORCAR, 3, [YELLOW_COAL, YELLOW_COAL]]),
      14 => $f([TRANSPORT_MOTORCAR, 3, [BROWN_COAL]]),
      15 => $f([TRANSPORT_MOTORCAR, 4, [YELLOW_COAL, YELLOW_COAL, YELLOW_COAL]]),
      16 => $f([TRANSPORT_MOTORCAR, 4, [GREY_COAL]]),
      17 => $f([TRANSPORT_MOTORCAR, 5, [BROWN_COAL, BROWN_COAL]]),
      18 => $f([TRANSPORT_MOTORCAR, 5, [BLACK_COAL]]),
      19 => $f([TRANSPORT_MOTORCAR, 7, [GREY_COAL, GREY_COAL]]),
      20 => $f([TRANSPORT_MOTORCAR, 9, [BLACK_COAL, BLACK_COAL]]),
      21 => $f([TRANSPORT_MOTORCAR, 10, [YELLOW_COAL, BROWN_COAL, GREY_COAL, BLACK_COAL]]),
      22 => $f([TRANSPORT_MOTORCAR, 12, [BLACK_COAL, BLACK_COAL, BLACK_COAL]]),
      // 11 different carriage
      23 => $f([TRANSPORT_CARRIAGE, 2, [YELLOW_COAL]]),
      24 => $f([TRANSPORT_CARRIAGE, 3, [YELLOW_COAL, YELLOW_COAL]]),
      25 => $f([TRANSPORT_CARRIAGE, 3, [BROWN_COAL]]),
      26 => $f([TRANSPORT_CARRIAGE, 4, [YELLOW_COAL, YELLOW_COAL, YELLOW_COAL]]),
      27 => $f([TRANSPORT_CARRIAGE, 4, [GREY_COAL]]),
      28 => $f([TRANSPORT_CARRIAGE, 5, [BLACK_COAL]]),
      29 => $f([TRANSPORT_CARRIAGE, 5, [BROWN_COAL, BROWN_COAL]]),
      30 => $f([TRANSPORT_CARRIAGE, 7, [GREY_COAL, GREY_COAL]]),
      31 => $f([TRANSPORT_CARRIAGE, 9, [BLACK_COAL, BLACK_COAL]]),
      32 => $f([TRANSPORT_CARRIAGE, 10, [YELLOW_COAL, BROWN_COAL, GREY_COAL, BLACK_COAL]]),
      33 => $f([TRANSPORT_CARRIAGE, 12, [BLACK_COAL, BLACK_COAL, BLACK_COAL]]),
      // 11 different engine
      34 => $f([TRANSPORT_ENGINE, 2, [YELLOW_COAL]]),
      35 => $f([TRANSPORT_ENGINE, 3, [BROWN_COAL]]),
      36 => $f([TRANSPORT_ENGINE, 3, [YELLOW_COAL, YELLOW_COAL]]),
      37 => $f([TRANSPORT_ENGINE, 4, [GREY_COAL]]),
      38 => $f([TRANSPORT_ENGINE, 5, [BROWN_COAL, BROWN_COAL]]),
      39 => $f([TRANSPORT_ENGINE, 5, [BLACK_COAL]]),
      40 => $f([TRANSPORT_ENGINE, 7, [BROWN_COAL, BROWN_COAL, BROWN_COAL]]),
      41 => $f([TRANSPORT_ENGINE, 7, [GREY_COAL, GREY_COAL]]),
      42 => $f([TRANSPORT_ENGINE, 9, [GREY_COAL, GREY_COAL, GREY_COAL]]),
      43 => $f([TRANSPORT_ENGINE, 9, [BLACK_COAL, BLACK_COAL]]),
      44 => $f([TRANSPORT_ENGINE, 10, [YELLOW_COAL, BROWN_COAL, GREY_COAL, BLACK_COAL]]),
    ];
  }
}
