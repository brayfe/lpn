# layout_per_node
Drag-and-drop editor for node display

## Setup & Usage
1. After enabling this module, go to any node content type's edit page (e.g., `/admin/structure/types/manage/page`)
2. Click the "**Layout Per Node**" vertical tab and tick "**Enable layout per node**"
- Optionally, define which layouts, provided by the system, should be available to choose from on this content type.
 - If no layouts are explicitly selected, all will be available.
4. Add a new node of that type and save data to any of its fields.
5. In the "view" mode of that node, you will see a new "**Layout Editor**" tab. Clicking it will enable drag-and-drop layout editing.
 - The "**Switch Layouts**" tab allows selection of any available layouts.
 - The "**Save Layout**" tab will write any staged changes to a new node revision
 - The "**Add Content**" button within each region will provide a pop-up for selecting any available fields or site-wide content (i.e., blocks)

## Re-usable content
Currently, layout_per_node makes available two categories of reusable content: Views blocks and custom blocks (of any block type). Eventually, this may expand to cover additional use cases.

### Layout Compatibility
This module does not provide any layouts. It finds and makes available all
layouts registered through the core Layout Discovery system.

Layout Discovery templates you provide are responsible for identifying each of their regions the via `region--<region-name>` CSS class syntax, which is required for the drag-and-drop UI to communicate placement. See `core/layout_discovery/layouts` for examples.

## Road map
- Add template thumbnails to the "Switch Layouts" form.

### Why "Layout Per *Node*"?
Drupal 8 has made great strides in standardizing Drupal's disparate 'content buckets' around the Entity API: in D8, taxonomies, blocks, and users share the same API as node types -- and as such, they are also all "fieldable" and can use `field_layout`.

There is, therefore, no inherent *technical* limitation to implementing Layout Per Node's drag-and-drop UI and layout storage backend to taxonomy pages, blocks, users, and arbitrary content entities created by other projects.

The maintainers' decision to limit the ability of the drag-and-drop UI to node types was therefore an attempt to best support the majority of use cases while scoping the code implementation for maintainability.

## Tests
All tests are run via PHPUnit, and can be executed singly via
`../../vendor/bin/phpunit --group=layout_per_node` (assuming a `web` docroot)
