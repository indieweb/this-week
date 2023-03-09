<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function testEventNameWasKeyword()
    {
        $summary = 'foo was an event...';
        $result = eventNameFromSummary($summary, 'default1');
        $this->assertEquals('foo', $result);
    }

    public function testEventNameWasKeyword2()
    {
        $summary = "foo wasn't an event...";
        $result = eventNameFromSummary($summary, 'default2');
        $this->assertEquals('default2', $result);
    }

    public function testEventNameIsKeyword()
    {
        $summary = 'bar is an event...';
        $result = eventNameFromSummary($summary, 'default3');
        $this->assertEquals('bar', $result);
    }

    public function testEventNameIsKeyword2()
    {
        $summary = "bar isn't an event...";
        $result = eventNameFromSummary($summary, 'default4');
        $this->assertEquals('default4', $result);
    }

    public function testEventNameFallback()
    {
        $summary = 'this summary does not include the is/was separator as a standalone word';
        $result = eventNameFromSummary($summary, 'default');
        $this->assertEquals('default', $result);
    }

    public function testEventDateFromTitle()
    {
        $result = eventDateFromTitle('events/2023-01-25-hwc-pacific');
        $this->assertEquals('2023-01-25', $result);
    }

    public function testEventDateFromTitleNotISO8601()
    {
        $result = eventDateFromTitle('events/01-25-2023-hwc-pacific');
        $this->assertNull($result);
    }

    public function testEventDateFromTitleNoDate()
    {
        $result = eventDateFromTitle('events/title-with-no-date');
        $this->assertNull($result);
    }

    public function testAddEventToList()
    {
        $events = [];
        $name = 'events/2023-01-25-hwc-pacific';
        $summary = 'Homebrew Website Club - Pacific was an IndieWeb meetup on Zoom held on 2023-01-25.';
        addEventToList($events, $name, $summary);

        # $events should have one event with one date
        $this->assertCount(1, $events);
        $this->assertArrayHasKey('Homebrew Website Club - Pacific', $events);
        $this->assertCount(1, $events['Homebrew Website Club - Pacific']);
    }

    public function testAddEventToListMultipleDates()
    {
        $events = [];

        $name = 'events/2023-01-25-hwc-pacific';
        $summary = 'Homebrew Website Club - Pacific was an IndieWeb meetup on Zoom held on 2023-01-25.';
        addEventToList($events, $name, $summary);

        $name = 'events/2023-01-11-hwc-pacific';
        $summary = 'Homebrew Website Club - Pacific was an IndieWeb meetup on Zoom held on 2023-01-11.';
        addEventToList($events, $name, $summary);

        # $events should have one event with two dates
        $this->assertCount(1, $events);
        $this->assertArrayHasKey('Homebrew Website Club - Pacific', $events);
        $this->assertCount(2, $events['Homebrew Website Club - Pacific']);
    }

    public function testAddEventToListMultipleEvents()
    {
        $events = [];

        $name = 'events/2023-01-18-hwc-europe';
        $summary = 'Homebrew Website Club Europe/London was an IndieWeb meetup on Zoom held on 2023-01-18.';
        addEventToList($events, $name, $summary);

        $name = 'events/2023-01-11-hwc-pacific';
        $summary = 'Homebrew Website Club - Pacific was an IndieWeb meetup on Zoom held on 2023-01-11.';
        addEventToList($events, $name, $summary);

        # $events should have two events, each with one date
        $this->assertCount(2, $events);
        $this->assertArrayHasKey('Homebrew Website Club Europe/London', $events);
        $this->assertCount(1, $events['Homebrew Website Club Europe/London']);
        $this->assertArrayHasKey('Homebrew Website Club - Pacific', $events);
        $this->assertCount(1, $events['Homebrew Website Club - Pacific']);
    }

    public function testBuildEventDateLinks()
    {
        $options = [
            'events/2023-01-25-hwc-pacific' => '2023-01-25',
            'events/2023-01-11-hwc-pacific' => '2023-01-11',
        ];
        $results = buildEventDateLinks($options);
        $this->assertCount(2, $results);
    }
}

