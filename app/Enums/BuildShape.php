<?php

namespace App\Enums;

/**
 * How a "build your own" fills its parts: one item cut into them (a pizza
 * half and half, each part its share of a whole), or whole pieces (three
 * scoops, each part a whole item).
 */
enum BuildShape: string
{
    case Split = 'split';
    case Pieces = 'pieces';
}
