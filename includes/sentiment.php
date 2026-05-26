<?php
// Sentiment Analysis for Reviews (Bonus Task 4)
// Simple lexicon-based sentiment classifier — no external API needed

function analyze_sentiment($text) {
    $text = strtolower($text);

    $positive = [
        'amazing','excellent','wonderful','fantastic','great','loved','love',
        'best','beautiful','perfect','outstanding','superb','brilliant','awesome',
        'highly recommend','recommend','good','nice','pleasant','enjoyed','enjoy',
        'delightful','impressive','exceeded','worth','incredible','memorable',
        'exceptional','stunning','magnificent','splendid','glad','happy',
        'comfortable','clean','friendly','professional','helpful','attentive',
        'seamless','smooth','easy','convenient','affordable','value','bargain'
    ];

    $negative = [
        'terrible','awful','horrible','worst','bad','poor','disappointed',
        'disappointing','waste','overpriced','expensive','rude','unprofessional',
        'dirty','uncomfortable','noisy','cramped','delayed','cancelled',
        'never again','avoid','scam','fraud','misleading','lied','broken',
        'unhelpful','slow','boring','mediocre','subpar','lacking','missing',
        'forgot','ignored','problem','issue','complaint','unhappy','hated'
    ];

    $posCount = 0;
    $negCount = 0;

    foreach ($positive as $word) {
        if (strpos($text, $word) !== false) $posCount++;
    }
    foreach ($negative as $word) {
        if (strpos($text, $word) !== false) $negCount++;
    }

    // Exclamation marks boost positive sentiment
    $excCount = substr_count($text, '!');

    $total = $posCount + $negCount + 1;
    $score = ($posCount + $excCount * 0.5 - $negCount) / $total;

    if ($score > 0.15) return ['label' => 'Positive', 'score' => round(min($score * 100, 100), 1)];
    if ($score < -0.1) return ['label' => 'Negative', 'score' => round(min(abs($score) * 100, 100), 1)];
    return ['label' => 'Neutral', 'score' => round(50 + $score * 50, 1)];
}

function sentiment_badge($result) {
    $color = $result['label'] === 'Positive' ? '#10b981' : ($result['label'] === 'Negative' ? '#ef4444' : '#94a3b8');
    return '<span class="badge" style="background:' . $color . ';color:#fff;margin-left:0.5rem;">' .
           $result['label'] . ' ' . $result['score'] . '%</span>';
}
