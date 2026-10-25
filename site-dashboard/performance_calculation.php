<?php
// Event timestamps mark the beginning; counters describe the recorded event.
function performanceAccumulate($events, $start, $end)
{
    $result = array('lengthValue'=>0.0, 'lengthReliable'=>true, 'lengthSamples'=>0, 'picks'=>0.0, 'weavingPicks'=>0.0, 'totalSeconds'=>0, 'weavingSeconds'=>0, 'stopCount'=>0, 'stopSeconds'=>0);
    foreach ($events as $event) {
        $timestamp = (int) $event['eventTimestamp'];
        $duration = max(0, (int) $event['TempoEvento']);
        $seconds = max(0, min($timestamp + $duration, $end) - max($timestamp, $start));
        $status = (int) $event['StatoProduzione'] % 256;
        $stopStart=isset($event['stopStart'])?$event['stopStart']:!(int)$event['IsShiftSameEvent'];
        if ($status >= 1 && $status <= 20 && $timestamp >= $start && $timestamp < $end && $stopStart) $result['stopCount']++;
        if (!$duration || !$seconds) continue;
        $fraction = $seconds / $duration;
        $picks = max(0, (int) $event['Battute']) * $fraction;
        $result['picks'] += $picks;
        $result['totalSeconds'] += $seconds;
        if ((int) $event['StatoProduzione'] === 0) {
            $result['weavingSeconds'] += $seconds;
            $result['weavingPicks'] += $picks;
        }
        if ($status >= 1 && $status <= 20) $result['stopSeconds'] += $seconds;
        if (!empty($event['sequenceGap'])) { $result['lengthReliable']=false; continue; }
        if ($event['previousLength'] !== null) {
            $length = max(0, (int) $event['LunghezzaDaInizio']);
            $delta = $length - (int) $event['previousLength'];
            $pieceChanged = isset($event['previousPiece'], $event['NumeroPezza']) && (int)$event['previousPiece'] !== (int)$event['NumeroPezza'];
            if ($pieceChanged) {
                $result['lengthSamples']++;
                $result['lengthValue'] += $length * $fraction / 1000;
            } elseif ($delta >= 0) {
                $result['lengthSamples']++;
                $result['lengthValue'] += $delta * $fraction / 1000;
            } else {
                // A correction within the same piece is not a new piece's
                // production. The amount woven during this event is unknown.
                $result['lengthReliable'] = false;
            }
        } elseif ($picks > 0) {
            $result['lengthReliable'] = false;
        }
    }
    return $result;
}
