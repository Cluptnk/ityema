<?php
require_once __DIR__ . '/../site-dashboard/performance_calculation.php';
function eventRow($time, $seconds, $status, $picks, $previous, $length, $carry = 0, $previousPiece = 1, $piece = 1) {
    return array('eventTimestamp'=>$time,'TempoEvento'=>$seconds,'StatoProduzione'=>$status,'Battute'=>$picks,'previousLength'=>$previous,'LunghezzaDaInizio'=>$length,'IsShiftSameEvent'=>$carry,'previousPiece'=>$previousPiece,'NumeroPezza'=>$piece);
}
function checkValue($actual, $expected, $name) {
    if (abs($actual-$expected)>0.00001) throw new RuntimeException($name . ': expected ' . $expected . ', got ' . $actual);
}
// A running event crosses the start and end boundaries: only half is counted.
$r=performanceAccumulate(array(eventRow(0,120,0,600,1000,3000)),30,90);
checkValue($r['weavingSeconds'],60,'boundary duration');
checkValue($r['picks'],300,'boundary picks');
checkValue($r['lengthValue'],1,'boundary length');
// A reset must preserve production before and after the reset.
$r=performanceAccumulate(array(eventRow(0,60,0,300,1000,2000),eventRow(60,60,0,300,2000,500,0,1,2)),0,120);
checkValue($r['lengthValue'],1.5,'counter reset');
// Actual source anomaly: 399.53 m -> 302.23 m in the same piece.
// Treating the latter as new production would invent 302.23 m.
$r=performanceAccumulate(array(eventRow(0,1800,0,8103,399530,302230)),0,1800);
checkValue($r['lengthValue'],0,'same-piece counter correction');
checkValue((int)$r['lengthReliable'],0,'counter correction warning');
// A new piece can have a larger counter than a very short prior piece.
$r=performanceAccumulate(array(eventRow(0,60,0,300,100,500,0,1,2)),0,60);
checkValue($r['lengthValue'],0.5,'piece change independent of counter ordering');
// Verified 2026-10-22, loom 14: piece 211 finishes, then piece 212 starts.
$r=performanceAccumulate(array(
    eventRow(0,60,0,100,454210,501040,0,211,211),
    eventRow(60,60,18,0,501040,0,0,211,212),
    eventRow(120,60,0,100,0,20390,0,212,212)
),0,180);
checkValue($r['lengthValue'],67.22,'verified loom 14 production');
checkValue((int)$r['lengthReliable'],1,'verified loom 14 counters');
// Reverse picks in a stop must not lower the weaving speed's numerator.
$r=performanceAccumulate(array(eventRow(0,60,0,300,1000,2000),eventRow(60,60,257,-2,2000,2000)),0,120);
checkValue($r['weavingPicks'],300,'weaving picks');
checkValue($r['stopSeconds'],60,'stop duration');
checkValue($r['stopCount'],1,'stop count');
// A stop carried across a shift is time in this window, not a new stop.
$r=performanceAccumulate(array(eventRow(0,120,18,0,1000,1000)),60,120);
checkValue($r['stopSeconds'],60,'carried stop duration');
checkValue($r['stopCount'],0,'carried stop count');
$r=performanceAccumulate(array(eventRow(60,60,18,0,1000,1000,1)),60,120);
checkValue($r['stopCount'],0,'shift duplicate count');
// Missing baseline and future periods cannot manufacture production.
$r=performanceAccumulate(array(eventRow(100,60,0,300,null,2000)),200,150);
checkValue($r['lengthValue'],0,'future length');
checkValue($r['totalSeconds'],0,'future time');
echo "Performance calculation checks passed.\n";
