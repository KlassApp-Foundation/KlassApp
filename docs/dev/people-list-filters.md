# People list filters (developer note)

The students, teachers and parents lists share one component: `resources/views/components/people-list.blade.php`, fed by `app/Services/People/PeopleListQuery.php`.

## Query keys

`PeopleListQuery::filters()` reads these request keys (`PeopleListQuery.php:383`):

| Key | Meaning |
| --- | --- |
| `search` | Name or identifier search |
| `chip` | A quick filter chip (for example active / inactive) |
| `standard` | The base class id |
| `stream` | A stream (standard link) id |
| `status` | Active / inactive |

`standard` and `stream` are ids, not names. A stream id that does not belong to the selected class is ignored (`PeopleListQuery.php:415-418`).

## Links

Chip and stream links are built with the current query string, so filtering is shareable and bookmarkable. `clear_url` drops all five keys. The view renders the class menu at `people-list.blade.php:37-52`, the stream row at `:60`, and **Clear filters** at `:115`.

## Adding a filter

1. Read the key in `PeopleListQuery::filters()`.
2. Apply it in the query builder, scoped by `school_id`.
3. Add the chip or menu to the view; keep the label plain.
4. Add a feature test that asserts the filtered count and the URL.
