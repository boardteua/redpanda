<?php

namespace App\Services\Ai\RudaPanda;

enum RudaPandaIntent: string
{
    /** Короткі репліки, привітання, прості питання. */
    case Simple = 'simple';

    /** Довгі/глибокі запити; для VIP може маршрутизуватись на Pro (T176). */
    case Complex = 'complex';

    /** Запит на зображення (маршрут моделі для T181; права — окремо). */
    case Image = 'image';
}
