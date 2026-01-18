<?php

namespace Diversworld\ContaoDiveclubBundle\Model;

use Contao\CoreBundle\DependencyInjection\Attribute\AsModel;
use Contao\CalendarEventsModel;

#[AsModel(table: 'tl_calendar_events')]
class DcCalendarEventsModel extends CalendarEventsModel
{
    // Standard-Tabelle
    protected static $strTable = 'tl_calendar_events';
}
