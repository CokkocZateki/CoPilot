<?php
/**
 * Copyright © 2016 RZN
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
/**
 * Created for CoPilot.
 * User: RZN
 * Date: 3/4/2016
 * Time: 01:00
 */

//** H2o Filter functions */
\H2o::addFilter('month');
\H2o::addFilter('crestType');
\H2o::addFilter('secColor');
function month($month)
{
    return date("F", mktime(0, 0, 0, $month, 10));
}

/** typeID -> typeName */
function crestType($id)
{
    if ($id <= 0)
        return 'Unknown';

    return \CoPilot\eveCrest::singleton()->getItemTypes($id)->name;
}

/** security status colors */
function secColor($sec)
{
    $secMod = $sec * 10;
    $colors = array('#F00000', '#D73000', '#F04800','#F06000','#D77700',
        '#EFEF00','#8FEF2F','#00F000','#00EF47','#48F0C0','#2FEFEF');

    if($secMod <= 10 && $secMod >= 0)
        return $colors[$secMod];
    return '#F30202';
}

?>