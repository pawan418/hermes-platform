<?php

namespace App\Modules\Calendar\Services;

class CalendarService
{
    /**
     * Generate standard iCalendar (.ics) formatted payload for emails and links.
     */
    public function generateIcsInvite(
        string $title,
        \DateTime $startAt,
        int $durationMinutes,
        string $description = 'Scheduled via Hermes AI Platform'
    ): string {
        $endAt = clone $startAt;
        $endAt->modify("+{$durationMinutes} minutes");

        $dtStart = $startAt->format('Ymd\THis\Z');
        $dtEnd = $endAt->format('Ymd\THis\Z');
        $dtStamp = gmdate('Ymd\THis\Z');
        $uid = uniqid('hermes_meeting_');

        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Longway Softronix//Hermes AI Platform//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$dtStamp}",
            "DTSTART:{$dtStart}",
            "DTEND:{$dtEnd}",
            "SUMMARY:{$title}",
            "DESCRIPTION:{$description}",
            'SEQUENCE:0',
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $ics);
    }
}
