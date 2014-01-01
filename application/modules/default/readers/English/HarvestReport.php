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
class Default_Reader_English_HarvestReport
{

    /**
     * Parse a harvest report
     *
     * @param string $source
     *
     * @return array  of {@link Default_Model_HarvestReport}'s
     */
    public function parse($source)
    {
        $reports = array();

        /**
         * Example report:
			 Your recycler(s) (1) have a total cargo capacity of 20.000.
			 At the target [1:305:15], 0 Metal and 3.000 Crystal are floating in space. 
			 You have harvested 0 Metal and 3.000 Crystal.
         */
        $regex  = 'Your recycler\(s\) \(([0-9.]*?)\) cargo capacity of ([0-9.]*?).';
        $regex .= 'At the target \[([0-9.]*?:[0-9.]*?:[0-9.]*?)\], ([0-9.]*?) metal and ([0-9.]*?) crystal are floating in space.';
        $regex .= 'have harvested ([0-9.]*?) metal and ([0-9.]*?) crystal';

        $matches = array();

        preg_match_all('/' . $regex . '/i', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $reports[] = new Default_Model_HarvestReport(
                (float) str_replace('.', '', $match[1]),
                (float) str_replace('.', '', $match[2]),
                (float) str_replace('.', '', $match[3]),
                (float) str_replace('.', '', $match[4]),
                (float) str_replace('.', '', $match[5]),
				(float) str_replace('.', '', $match[6]),
                (float) str_replace('.', '', $match[7])
            );
        }

        return $reports;
    }
}
