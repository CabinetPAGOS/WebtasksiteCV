<?php

namespace App\Services;

class TextTransformer
{
    public function transformCrToNewLine(?string $text): string
    {
        // Si $text est null, retourner une chaîne vide
        if ($text === null) {
            return '';
        }

        // Sinon, remplacer les occurences de '<cr/>' par un retour à la ligne
        return str_replace('<cr/>', "\n", $text);
    }
}
