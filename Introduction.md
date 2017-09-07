# Layout Per Node
#### This module allows content builders to choose, on a per-page basis, between available page layouts and assign both reusable content and page-specific content.


Layout Per Node ([layout_per_node](https://www.drupal.org/project/layout_per_node)), a project from developers at the University of Texas, sets out to solve one of the central goals of Drupal's [Layout Initiative](https://groups.drupal.org/scotch), namely [emphasis ours] to:

> ...bring unity to a system of disjointed output components (blocks, page callbacks, menus, theme settings, and more) and **provide a standardized mechanism of output, new tools for placing content on a page**, and a potential for performance gains amongst other benefits.

Moreover, this project's goal is to be a content placement tool that is **simple and intuitive**.

If you're familiar with [Panels](https://www.drupal.org/project/panels), another way of saying that is: Layout Per Node is a take on "Panels Lite."

This walkthrough summarizes Layout Per Node's content building workflow and its minimal setup & configuration, and then provides commentary on the technical implementation and its niche within the Layout Initiative landscape.

### 1. Content Building
Let's start with the actual use of Layout Per Node: how a content builder would use it, and how it integrates into Drupal's content editing conventions.

We start with a standard Drupal "article" node, consisting of `body` field, `image` field, `tags` field, and `comments` field. Integrated with the `View`, `Edit` and `Delete` tabs is a new `Layout` tab. Clicking on this tab enables the field_layout region overlay, which defaults to the single content region.

The `Switch Layouts` tab now becomes visible; this provides an interface for switching between any of the allowed layouts for the given node type. These layouts are not provided by this module, but are part of the core Layout Discovery system, which allows modules to register their own layouts.

Let's first switch to one of the Drupal-provided two-column layouts.

Next, we will place content in the newly visible layout regions using any of the `Add content` buttons at the base of each region. The `Add Content` overlay provides a listing of both our node-specific content, namely, the body, image, tags, and comments fields, as well as content which is not associated with a specific node, that is, Drupal blocks -- which are separated into custom block types and Views blocks.

The `Add content` overlay provides rendered previews of each available element to better disambiguate content.

Now that we have added content to the regions, we have the ability to rearrange or remove elements via a drag-and-drop UI. Once we're satisfied, we can choose to `Save Layout`, which creates a new node revision and saves the layout data.

The ability to revision layouts not only enables reverting not just content *and* content placement, but also the ability to draft new content *and* content placement via Drupal's Workflow module.

### 2. Setup & Configuration
Now that we've seen the end product, let's see what it takes to integrate Layout Per Node in a site. After enabling the module, visit any node type's "Edit" interface. A new `Layout Per Node` vertical tab appears among the others. After ticking the on/off checkbox, a list of all layouts registered via Drupal's Layout Discovery system appear.

Once enabled, a new permission definition is added to the permissions system for using the node type's layout per node. In this sense, Layout Per Node is treated as simply another facet of the content building permissions stack, alongside creating, editing, and deleting node content. Via these permissions, a given site can allow some users to create content and others to manage its layout; another site could grant separate roles to create and layout content for each distinct node type.


### 3. Differences from Panels, Display Suite, and Paragraphs
Panels, combined with In-Place Editor (IPE) and Page Manager, has been and continues to be one of the *de facto* approaches to content layout. It is is perhaps the most comprehensive approach, allowing fine-grained control over elements such as field labels, Views contextual filters, and view modes, which arguably comes at a cost feeling either overwhelming for the content builder or providing too much control.

It also exposes all block types provided by all modules immediately, and allows
the ability to create new content, such as basic blocks, directly via the In-Place Editor.

Layout Per Node takes an opt-in approach for which block types should be made available to content builders. It does not allow per-instance configuration of blocks, for example, whether or not to display the title, but inherits this from
the block configuration itself.

Perhaps the most signficant difference, however, is how each approach handles
node fields. Panels allows placing node content on a page via Chaos Tools, which
allows access to entity view modes. From there, the user can choose between any
predefined view mode to render the node content in different variants. However,
this requires an appropriate view mode to exist, and most importantly,
it doesn't allow placement of individual fields separate from each other.

Layout Per Node thinks of fields and block the same way: they are individual
bits of content are placeable anywhere within selected layout.

(Add a paragraph on Display Suite)

(Add a paragraph on Paragraphs)


### 4. Extensibility/Coexistence
Layout Per Node's responsibility within the Drupal content building ecosystem is narrow by design:
- By relying on the `field_layout` paradigm -- and therefore functioning at the node level -- it is not dependent on regions defined by a given theme; indeed Layout Per Node works equally with any theme.
- By relying on the same Layout Discovery system as Panels and Display Suite, it can be used in conjunction with them. Panels IPE can be used for one node type, and Layout Per Node can be used for another on the same site.
- Moreover, layouts developed by and for one layout system can be used interchangeably with Layout Per Node, provided they follow Layout Discovery standards.*

### 5. Why "Per *Node*"?
Drupal 8 has made great strides in standardizing Drupal's disparate 'content buckets' around the Entity API: in D8, taxonomies, blocks, and users share the same API as node types -- and as such, they are also all "fieldable" and can use `field_layout`.

There is, therefore, no inherent *technical* limitation to implementing Layout Per Node's drag-and-drop UI and layout storage backend to taxonomy pages, blocks, users, and arbitrary content entities created by other projects.

The maintainers' decision to limit the ability of the drag-and-drop UI to node types was therefore an attempt to best support the majority of use cases while scoping the code implementation for maintainability.

/* Layout Discovery templates are responsible for identifying each of their regions the via `region--<region-name>` CSS class syntax, which is required for the drag-and-drop UI to communicate placement. See `core/layout_discovery/layouts` for examples.
