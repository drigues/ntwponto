<?php

namespace App\Enums;

enum TipoMarcacao: string
{
    case Entrada = 'entrada';
    case InicioPausa = 'inicio_pausa';
    case FimPausa = 'fim_pausa';
    case Saida = 'saida';
}
