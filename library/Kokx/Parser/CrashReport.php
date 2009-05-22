<?php
/**
 * Ogame Converter
 *
 * @category   Kokx
 * @package    Kokx_Parser
 */

/**
 * CR parser
 *
 * @category   Kokx
 * @package    Kokx_Parser
 */
class Kokx_Parser_CrashReport
{

    const ATTACKER = 'attacker';
    const DEFENDER = 'defender';
    const NONE     = 'none';

    /**
     * Source
     *
     * @var string
     */
    protected $_source = '';

    /**
     * All the round data
     *
     * @var array
     */
    protected $_rounds = array();

    /**
     * Time of the battle
     *
     * @var array
     */
    protected $_time = array(
        'time' => '',
        'date' => ''
    );

    /**
     * The battle's result
     *
     * @var array
     */
    protected $_result = array(
        'winner'         => '', // 'none', 'defender' or 'attacker'
        'attackerlosses' => 0,  // the attacker's losses
        'defenderlosses' => 0,  // the defender's losses
    	'stolen'         => array( // the number of stolen goods
            'metal'   => 0,
            'crystal' => 0,
            'deut'    => 0
        ),
        'debris' => array( // debris field
            'metal'   => 0,
            'crystal' => 0
        ),
        'moonchance' => 0
    );


    /**
     * Get all the rounds
     *
     * @return array
     */
    public function getRounds()
    {
        return $this->_rounds;
    }

    /**
     * Get the battle time
     *
     * @return array
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * Get the battle result
     *
     * @return array
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Parse a crash report
     *
     * @return array
     */
    public function parse($source)
    {
        $this->_source = strstr($source, 'De volgende vloten kwamen elkaar tegen op');

        // check the CR
        if (false === $this->_source) {
            throw new Kokx_Parser_Exception('Bad CR');
        }

        $matches = array();

        preg_match('#^De volgende vloten kwamen elkaar tegen op ([0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2}) , toen het tot een gevecht kwam::#i', $this->_source, $matches);

        $this->_time['date'] = $matches[1];
        $this->_time['time'] = $matches[2];

        $this->_source = substr($this->_source, strlen($matches[0]));

        $this->_rounds = array($this->_parseFirstRound());

        // something is wrong here
        do {
            $this->_rounds[] = $this->_parseRound();
        } while (preg_match('#De aanvallende vloot vuurt#i', $this->_source));

        $this->_parseResult();

        return $this;
    }

    /**
     * Parse the first round
     *
     * @return array
     */
    protected function _parseFirstRound()
    {
        $round = array(
            'attackers' => array(),
            'defenders' => array()
        );

        // first find the first attacker
        $this->_source = strstr($this->_source, 'Aanvaller');

        // complicated regex that extracts all info from a fleet slot
        $regex = '(Aanvaller|Verdediger) (.*?) \(\[([0-9]:[0-9]{1,3}:[0-9]{1,2})\]\)\s*'
               . 'Wapens: ([0-9]{1,2})0% Schilden: ([0-9]{1,2})0% Romp beplating: ([0-9]{1,2})0%\s*'
               . 'Soort([A-Za-z.\t]*)\s*'
               . 'Aantal([0-9.\t]*)'
               . '.*?(Aanvaller|Verdediger|De aanvallende vloot vuurt)';

        $matches = array();
        // loop trough the text until we have found all fleets in the round
        while (preg_match('#' . $regex . '#s', $this->_source, $matches)) {
            $this->_source = substr($this->_source, strlen($matches[0]) - strlen($matches[9]));

            // extract the info from the matches array
            $info = array(
                'player' => array(
                    'name'   => $matches[2],
                    'coords' => $matches[3],
                    'techs'  => array(
                        'weapon' => (int) $matches[4],
                        'shield' => (int) $matches[5],
                        'armor'  => (int) $matches[6],
                    )
                ),
                'fleet' => array()
            );

            // add the fleet info
            $ships   = explode("\t", trim($matches[7]));
            $numbers = explode("\t", trim($matches[8]));

            foreach ($ships as $key => $ship) {
                $info['fleet'][$ship] = $numbers[$key];
            }

            // check if it is an attacker or a defender
            if ($matches[1] == 'Aanvaller') {
                $round['attackers'][] = $info;
            } else {
                $round['defenders'][] = $info;
            }

            // end the loop when we have all the fleets
            if ($matches[9] == 'De aanvallende vloot vuurt') {
                break;
            }

            // always reset this array at the end
            $matches = array();
        }

        return $round;
    }

    /**
     * Parse a round
     *
     * @return array
     */
    protected function _parseRound()
    {
        $round = array(
            'attackers' => array(),
            'defenders' => array()
        );

        // first find the first attacker
        $this->_source = strstr($this->_source, 'Aanvaller');

        // complicated regex that extracts all info from a fleet slot
        $regex = '(Aanvaller|Verdediger) (.*?) \(\[([0-9]:[0-9]{1,3}:[0-9]{1,2})\]\)\s*'
               . '(Soort([A-Za-z.\t]*)\s*' . 'Aantal([0-9.\t]*)' . '|Vernietigd)\s*'
               . '.*?(Aanvaller|Verdediger|De aanvallende vloot vuurt|De aanvaller heeft|De verdediger heeft|remise)';

        $matches = array();
        // loop trough the text until we have found all fleets in the round
        while (preg_match('#' . $regex . '#s', $this->_source, $matches)) {
            $this->_source = substr($this->_source, strlen($matches[0]) - strlen($matches[7]));

            // extract the info from the matches array
            $info = array(
                'player' => array(
                    'name'   => $matches[2],
                    'coords' => $matches[3]
                ),
                'fleet' => array()
            );

            // if the fleet isn't destroyed, add it to the info
            if ($matches[4] != 'Vernietigd') {
                $ships   = explode("\t", trim($matches[5]));
                $numbers = explode("\t", trim($matches[6]));

                foreach ($ships as $key => $ship) {
                    $info['fleet'][$ship] = $numbers[$key];
                }
            }

            // check if it is an attacker or a defender
            if ($matches[1] == 'Aanvaller') {
                $round['attackers'][] = $info;
            } else {
                $round['defenders'][] = $info;
            }

            // end the loop when we have all the fleets
            if (($matches[7] == 'De aanvallende vloot vuurt')
            || ($matches[7] == 'De aanvaller heeft')
            || ($matches[7] == 'De verdediger heeft')
            || ($matches[7] == 'remise')) {
                break;
            }

            // always reset this array at the end
            $matches = array();
        }

        return $round;
    }

    /**
     * Parse the battle's result
     *
     * @return void
     */
    protected function _parseResult()
    {
        // check who has won the fight
        if (preg_match('#gewonnen#i', $this->_source)) {
            if (preg_match('#De aanvaller heeft#i', $this->_source)) {
                $this->_result['winner'] = self::ATTACKER;

                // the attacker won, get the number of stolen resources

                $regex = 'De aanvaller steelt\s*?([0-9.]*) Metaal, ([0-9.]*) Kristal en ([0-9.]*) Deuterium';

                $matches = array();
                preg_match('#' . $regex . '#si', $this->_source, $matches);

                $this->_result['stolen'] = array(
                    'metal'   => (int) str_replace('.', '', $matches[1]),
                    'crystal' => (int) str_replace('.', '', $matches[2]),
                    'deut'    => (int) str_replace('.', '', $matches[3]),
                );
            } else {
                $this->_result['winner'] = self::DEFENDER;
            }
        } else {
            $this->_result['winner'] = self::NONE;
        }

        // get the attacker's losses
        $matches = array();
        preg_match('#De aanvaller heeft een totaal van ([0-9.]*) Eenheden verloren.#i', $this->_source, $matches);

        $this->_result['attackerlosses'] = (int) str_replace('.', '', $matches[1]);

        // get the defender's losses
        $matches = array();
        preg_match('#De verdediger heeft een totaal van ([0-9.]*) Eenheden verloren.#i', $this->_source, $matches);

        $this->_result['defenderlosses'] = (int) str_replace('.', '', $matches[1]);

        // get the debris
        $matches = array();
        preg_match('#in de ruimte zweven nu ([0-9.]*) Metaal en ([0-9.]*) Kristal.#i', $this->_source, $matches);

        $this->_result['debris']['metal']   = (int) str_replace('.', '', $matches[1]);
        $this->_result['debris']['crystal'] = (int) str_replace('.', '', $matches[2]);

        // moonchance
        $matches = array();
        if (preg_match('#De kans dat een maan ontstaat uit het puin is ([0-9]{1,2})#i', $this->_source, $matches)) {
            $this->_result['moonchance'] = (int) str_replace('.', '', $matches[1]);
        }
    }
}