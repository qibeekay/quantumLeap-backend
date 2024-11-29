<?php

namespace Enums;

enum CharacterPersonality: string
{
    case Leadership = 'Leadership';
    case Teamwork = 'Teamwork';
    case Maturity = 'Maturity';
    case Sportsmanship = 'Sportsmanship';
    case Goal = 'Goal';
}

enum WorkEthic: string
{
    case Dedication = 'Dedication';
    case Drive = 'Drive';
    case Resilience = 'Resilience';
}

enum GameSense: string
{
    case Awareness = 'Awareness';
    case Decision = 'Decision';
    case Positioning = 'Positioning';
    case Anticipation = 'Anticipation';
}

enum SportType: string
{
    case Basketball = 'basketball';
    case Volleyball = 'volleyball';
    case Football = 'football';

}

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
}

enum Level: string
{
    case Pro = 'pro';
    case College = 'college';
    case Highschool = 'highschool';
}