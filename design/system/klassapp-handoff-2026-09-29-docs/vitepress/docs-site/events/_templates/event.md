---
title: Set up your school in one afternoon
type: Webinar
start: 2026-10-16T12:00:00Z   # always UTC
minutes: 60
host: Africa/Kampala          # IANA zone of the host, shown as "Host time"
where: Online
summary: A live walk-through of setup on a demo school.
register: https://klassapp.xyz/events/register?e=setup
recording:                    # add after the event
status: preview
sidebar: false
aside: false
---
<PreviewLabel />

# {{ $frontmatter.title }}
{{ $frontmatter.summary }}

<div class="ka-timecard">
  <EventTime :start="$frontmatter.start" :minutes="$frontmatter.minutes" :host="$frontmatter.host" variant="card" />
  <a class="ka-cta-btn" :href="$frontmatter.register">Register</a>
  <AddToCalendar :title="$frontmatter.title" :start="$frontmatter.start" :minutes="$frontmatter.minutes" :url="$page.relativePath" />
</div>

## What we'll cover
| Time | |
|---|---|
| 0:00 | Welcome, and what KlassApp is |
| 0:10 | Setting up classes, streams and terms on Demo Junior School |
| 0:30 | Adding teachers and fees |
| 0:45 | Your questions |

## Who it's for
School admins and head teachers at schools from nursery to secondary.

## How to join
Register, and we'll email you the meeting link and a calendar invite.
