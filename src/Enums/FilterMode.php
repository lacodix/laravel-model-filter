<?php

namespace Lacodix\LaravelFilter\Enums;

enum FilterMode
{
    case EQUAL;
    case NOT_EQUAL;
    case GREATER;
    case LOWER;
    case GREATER_OR_EQUAL;
    case LOWER_OR_EQUAL;
    case LIKE;
    case STARTS_WITH;
    case ENDS_WITH;
}
