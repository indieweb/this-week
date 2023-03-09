# This Week in the IndieWeb

Publishes the [This Week in the IndieWeb](https://indieweb.org/this-week-in-the-indieweb) newsletter.

## Install

- Clone this repository
- Run `composer install`
- Open the root directory and run `php ./vendor/tantek/cassis/post-process.php`
  - This is a temporary workaround to ensure CASSIS runs under PHP 8+
- Rename `config.template.php` to `config.php`

## Configuration

TODO

## Testing

Run `composer tests` to run unit tests.

Run `php ./tests/test-wiki-summary.php` to test generating the wiki
summary part of the newsletter. This will save the output in
`/tests/wiki-summary-output.html` for manual review.

You can try providing an end date using:
`php ./tests/test-wiki-summary.php YYYY-MM-DD`

However, results with this can be mixed. The MediaWiki API appears
to not be respecting the end date and including newer pages in the
API response. TODO: investigate further, see https://www.mediawiki.org/wiki/API:RecentChanges

