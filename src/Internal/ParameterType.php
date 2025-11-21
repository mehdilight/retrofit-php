<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

enum ParameterType
{
    case Path;
    case Query;
    case QueryMap;
    case Body;
    case Field;
    case FieldMap;
    case Part;
    case PartMap;
    case Header;
    case HeaderMap;
    case Url;
}
