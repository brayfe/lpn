# layout_per_node
Drag-and-drop editor for node display

# Setup & Usage
1. After enabling this module, go to any node content type's edit page (e.g., /admin/structure/types/manage/page)
2. Click the "Layout Editor" vertical tab and tick "Enable layout per node"
3. Optionally, define which layouts, provided by the system, should be available to choose from on this content type
4. Add a new node of that type and save data to any of its field.
5. On the "view" page of that node, you will see a new "Layout Editor" tab. Clicking it will enable drag-and-drop layout editing.
The "Switch Layouts" tab will also appear, allowing selection of any available layouts.

# Re-usable content
Currently, layout_per_node makes available two categories of reusable content: Views blocks and custom blocks (of any block type). Eventually, this may expand to cover additional use cases.

# Road map
- Add template thumbnails to the "Switch Layouts" form.

## Tests
All tests are run via PHPUnit, and can be executed singly via
`../../vendor/bin/phpunit --group=layout_per_node` (assuming a `web` docroot)
