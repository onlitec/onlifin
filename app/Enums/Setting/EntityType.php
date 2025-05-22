<?php

namespace App\Enums\Setting;

enum EntityType: string
{
    case SoleProprietorship = 'sole_proprietorship';
    case GeneralPartnership = 'general_partnership';
    case LimitedPartnership = 'limited_partnership';
    case LimitedLiabilityPartnership = 'limited_liability_partnership';
    case LimitedLiabilityCompany = 'limited_liability_company';
    case Corporation = 'corporation';
    case Nonprofit = 'nonprofit';
} 