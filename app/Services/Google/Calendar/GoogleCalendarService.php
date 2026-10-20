<?php

namespace App\Services\Google\Calendar;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Events;
use App\Models\UserGoogleConnection;
use App\Models\User;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use App\Support\DashboardGreeting;
use Carbon\CarbonInterface;

class GoogleCalendarService
{
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleConnectorCatalog $catalog,
        private readonly GoogleConnectionService $connections,
    ) {
    }

    /**
     * Publish the current academic year's term dates and active school events
     * onto a calendar this app created. A second run updates those events and
     * removes ones that are no longer active.
     *
     * @return array{calendarId: string, terms: int, events: int}
     */
    public function publish(User $actor): array
    {
        $schoolId = (int) $actor->school_id;
        $connection = $this->connection($actor);
        $year = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        if ($year === null) {
            throw new GoogleApiException(404, 'This school has no current academic year.', 'no_academic_year');
        }

        $calendarId = $this->ensureCalendar($connection, DashboardGreeting::timezoneFor($actor));
        $items = $this->desired($schoolId, $year);
        $this->deleteStale($connection, $calendarId, array_column($items, 'id'));

        $terms = 0;
        $events = 0;

        foreach ($items as $item) {
            $this->upsert($connection, $calendarId, $item['id'], $item['body']);
            if (str_starts_with($item['id'], 'kappterm')) {
                $terms++;
            } else {
                $events++;
            }
        }

        return [
            'calendarId' => $calendarId,
            'terms' => $terms,
            'events' => $events,
        ];
    }

    private function connection(User $actor): UserGoogleConnection
    {
        if ((int) $actor->usergroup_id !== 3) {
            abort(404);
        }

        $connection = $this->connections->forUser($actor);

        if (! $this->catalog->isLive($connection, 'calendar')) {
            throw new GoogleApiException(404, 'Google Calendar is not connected for this user.', 'not_connected');
        }

        return $connection;
    }

    private function ensureCalendar(UserGoogleConnection $connection, string $timeZone): string
    {
        $existing = (string) $connection->google_calendar_id;

        if ($existing !== '' && $this->safeId($existing) && $this->calendarExists($connection, $existing)) {
            return $existing;
        }

        $created = $this->client->postJson($connection, $this->url('/calendar/v3/calendars'), [
            'summary' => 'KlassApp',
            'timeZone' => $timeZone,
        ]);
        $id = (string) ($created['id'] ?? '');

        if (! $this->safeId($id)) {
            throw new GoogleApiException(502, 'Google Calendar did not return a calendar id.');
        }

        $connection->forceFill(['google_calendar_id' => $id])->save();

        return $id;
    }

    private function calendarExists(UserGoogleConnection $connection, string $calendarId): bool
    {
        try {
            $this->client->get($connection, $this->url('/calendar/v3/calendars/'.rawurlencode($calendarId)));

            return true;
        } catch (GoogleApiException $e) {
            if ($e->status === 404) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @return list<array{id: string, body: array<string, mixed>}>
     */
    private function desired(int $schoolId, AcademicYear $year): array
    {
        $items = [];

        $terms = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->orderBy('id')
            ->get();

        foreach ($terms as $term) {
            if ($term->starts_on === null) {
                continue;
            }

            $start = $term->starts_on->copy()->startOfDay();
            $end = $term->ends_on instanceof CarbonInterface
                ? $term->ends_on->copy()->startOfDay()
                : $start->copy();

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            $items[] = [
                'id' => $this->googleEventId('term', (int) $term->id),
                'body' => [
                    'summary' => $this->summary((string) $term->name),
                    'start' => ['date' => $start->toDateString()],
                    'end' => ['date' => $end->copy()->addDay()->toDateString()],
                ],
            ];
        }

        $events = Events::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($events as $event) {
            if ($event->start_date === null) {
                continue;
            }

            $items[] = [
                'id' => $this->googleEventId('event', (int) $event->id),
                'body' => $this->eventBody($event),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventBody(Events $event): array
    {
        if ((int) $event->allDay === 1) {
            $startDay = $event->start_date->copy()->startOfDay();
            $endDay = $event->end_date instanceof CarbonInterface
                ? $event->end_date->copy()->startOfDay()
                : $startDay->copy();
            if ($endDay->lt($startDay)) {
                $endDay = $startDay->copy();
            }

            return [
                'summary' => $this->summary((string) $event->title),
                'start' => ['date' => $startDay->toDateString()],
                'end' => ['date' => $endDay->copy()->addDay()->toDateString()],
            ];
        }

        $start = $event->start_date->copy()->utc();
        $end = $event->end_date instanceof CarbonInterface
            ? $event->end_date->copy()->utc()
            : $start->copy();

        if ($end->lte($start)) {
            $end = $start->copy()->addHour();
        }

        return [
            'summary' => $this->summary((string) $event->title),
            'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s\Z'), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s\Z'), 'timeZone' => 'UTC'],
        ];
    }

    /**
     * @param  list<string>  $keep
     */
    private function deleteStale(UserGoogleConnection $connection, string $calendarId, array $keep): void
    {
        $pageToken = null;

        for ($page = 0; $page < 5; $page++) {
            $query = ['maxResults' => 250, 'showDeleted' => 'false'];
            if (is_string($pageToken) && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }

            $listed = $this->client->get($connection, $this->url('/calendar/v3/calendars/'.rawurlencode($calendarId).'/events'), $query);

            foreach ($listed['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = (string) ($item['id'] ?? '');
                if (! $this->isOurs($id) || in_array($id, $keep, true)) {
                    continue;
                }
                $this->client->request($connection, 'DELETE', $this->url(
                    '/calendar/v3/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($id)
                ));
            }

            $pageToken = $listed['nextPageToken'] ?? null;
            if (! is_string($pageToken) || $pageToken === '') {
                break;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function upsert(UserGoogleConnection $connection, string $calendarId, string $eventId, array $body): void
    {
        $url = $this->url('/calendar/v3/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($eventId));

        try {
            $this->client->get($connection, $url);
            $this->client->putJson($connection, $url, $body);

            return;
        } catch (GoogleApiException $e) {
            if ($e->status !== 404) {
                throw $e;
            }
        }

        $this->client->postJson(
            $connection,
            $this->url('/calendar/v3/calendars/'.rawurlencode($calendarId).'/events'),
            $body + ['id' => $eventId],
        );
    }

    private function googleEventId(string $kind, int $id): string
    {
        $prefix = $kind === 'term' ? 'kappterm' : 'kappevent';

        return $prefix.$id;
    }

    private function isOurs(string $id): bool
    {
        return str_starts_with($id, 'kappterm') || str_starts_with($id, 'kappevent');
    }

    private function summary(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        if ($name === '') {
            return 'School event';
        }

        return mb_substr($name, 0, 200);
    }

    private function safeId(string $id): bool
    {
        return $id !== ''
            && preg_match('/^[A-Za-z0-9@._-]{1,200}$/', $id) === 1
            && ! str_contains($id, '..');
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.google.calendar_base_url'), '/').$path;
    }
}
