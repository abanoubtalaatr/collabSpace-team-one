<?php

namespace App\Enums;

enum ReportType: string
{
    case PROJECT = 'project';
    case TASK = 'task';
    case TEAM = 'team';
    case USER = 'user';

}
