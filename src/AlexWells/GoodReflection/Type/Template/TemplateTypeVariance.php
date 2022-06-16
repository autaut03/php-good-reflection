<?php

namespace AlexWells\GoodReflection\Type\Template;

enum TemplateTypeVariance
{
	case INVARIANT;
	case COVARIANT;
	case CONTRAVARIANT;
}
