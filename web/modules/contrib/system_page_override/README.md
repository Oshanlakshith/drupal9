## Summary

The system page override module enables the configuration of nodes as system
pages.
When a node type is configured as overridable for a certain system page,
it's possible to override the system page at the node edit page.
This way it's possible to make one node type configurable as the front page,
the other as the 404 page and another as the 403 page.
There is multilingual support, so different system pages can be configured for
different languages.

## Requirements

None.


## Installation

1. Install as usual, see http://drupal.org/node/1897420 for further information.

2. Grant permissions to the desired roles:

        Administer system page override settings

        Administer system page overrides

        Administer a node as system page

## Usage

To override a system page, first one or more content types must be configured to
be overridable.

When a user has the `Administer system page override settings` permission,
the page `/admin/config/system/system-page-override/settings` becomes available.
Here content types can be checked for the front, 404 and 403 page.

Now it's possible for users with a `Administer a node as system page` permission
to set a node of
the configured node types as a system page. To enable a node as system page,
select the associated checkbox under the tab `Systempage settings`.
This is possible for all available languages.

When a user has the `Administer system page overrides` permission, it's possible
to see and change the
system page overrides to all node paths. This is done in the
`/admin/config/system/system-page-override` page.
