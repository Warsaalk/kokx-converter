<?php
/**
 * This file is part of Kokx's CR Converter.
 *
 * Kokx's CR Converter is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Kokx's CR Converter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kokx's CR Converter.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   KokxConverter
 * @license    http://www.gnu.org/licenses/gpl.txt
 * @copyright  Copyright (c) 2009 Kokx
 * @package    Default
 * @subpackage Readers
 */

/**
 * HR parser
 *
 * @category   Kokx
 * @package    Default
 * @subpackage Readers_Dutch
 */
class Default_Reader_Dutch_deuteriumCosts
{

    /**
     * Parse a deuterium costs
     *
     * @param string $source
     *
     * @return array  of {@link Default_Model_DeuteriumCosts}'s
     */
    public function parse($source)
    {
        $reports = array();

        /**
         Example report:
		 
         Brandstofverbruik: 213.901 Deuterium
         */
        $regex  = 'Brandstofverbruik: ([0-9.]*?) Deuterium';

        $matches = array();

        preg_match_all('/' . $regex . '/i', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $reports[] = new Default_Model_DeuteriumCosts (
                (float) str_replace('.', '', $match[1])
            );
        }
        
        return $reports;
    }
}
