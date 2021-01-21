Configuration
=============

Site API has its own view configuration, available under ``ngcontent_view`` key. Aside from
:doc:`Query Type </reference/query_types>` configuration that is documented separately, this is
exactly the same as eZ Platform's default view configuration under ``content_view`` key. You can use
this configuration right after the installation, but note that it won't be used for full views
rendered for eZ Platform URL aliases right away. Until you configure that, it will be used only when
calling its controller explicitly with ``ng_content:viewAction``.

**Content on this page:**

.. contents::
    :depth: 1
    :local:

Configure handling of URL aliases
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To use Site API view rules for pages rendered from eZ Platform URL aliases, you have to enable it
for a specific siteaccess with the following semantic configuration:

.. code-block:: yaml

    netgen_ez_platform_site_api:
        system:
            frontend_group:
                override_url_alias_view_action: true

Here ``frontend_group`` is the siteaccess group (or a siteaccess) for which you want to activate the
Site API. This switch is useful if you have a siteaccess that can't use it, for example a custom
admin or intranet interface.

.. note::

    To use Site API view configuration automatically on pages rendered from eZ Platform URL aliases,
    you need to enable it manually per siteaccess.

Cross-siteaccess routing
~~~~~~~~~~~~~~~~~~~~~~~~

Cross-siteaccess routing is a feature that enables automatic linking to Locations in the same
Repository, but across different siteaccesses. This is intended for single Repository multisite
installations, where multiple siteaccesses are configured with different Content tree root Location
IDs. The feature is implemented at the router level, so it will work both from PHP and Twig.

.. note::

    Cross-siteaccess routing is enabled by default, but if needed it can be disabled per siteaccess
    with ``ng_cross_siteaccess_routing`` option.

The logic of choosing the right siteaccess is not straightforward, and resolving the best matching
one considers the following:

- Current siteaccess Content tree root Location ID
- Current siteaccess prioritized languages configuration
- Current siteaccess excluded URI prefixes configuration
- Matching siteaccess Content tree root Location ID
- Matching siteaccess prioritized languages configuration
- Matching siteaccess translation siteaccesses configuration
- Location subtree
- Location Content translations
- Location Content always available flag

.. note::

    The matching process is described below, but the rules are dense and it might be hard to
    understand all the implications right away. To better understand the matching logic, you are
    encouraged to look into the test cases. They were written to simulate the siteaccess
    configuration and to be easy to read.

Current siteaccess will always be preferred if it matches the context, meaning given Location's
subtree, available translations and always available flag. Otherwise, the siteaccess will be chosen
among the siteaccesses that do match the given context.

Current siteaccess is also a fallback that is used when nothing of the below matches the context.

In case when multiple (non current) siteaccesses match the context, the best matching one will be
chosen according to the current siteaccess configured prioritized languages. The matching logic will
respect the order/priority of the configured prioritized languages for both current and potentially
matching siteaccess, resulting in the selection of a siteaccess that can allows highest possible
language of the current siteaccess at a highest possible position on the matching siteaccess. The
important think to note here is that configured prioritized languages take precedence over the
available languages of the Location, which means that in some cases, the resulting siteaccess will
be the best one in regard to the prioritized languages, but not the best one in regard to the
Location's available languages.

It's possible that matching a siteaccess by the current siteaccess prioritized languages will
produce no result. In that case all siteaccesses matching the context will be checked, according to
their order in the configured siteaccess list.

It's also possible that multiple siteaccess will match the language configuration equally well. In
that case, the first one according to the configured siteaccess list will be used.

If so configured, matching siteaccess translation siteaccesses will be also considered in selecting
the siteaccess that will be used for linking. Only the most prioritized language of the translation
siteaccess will be considered, and matching will use current siteaccess prioritized languages to
select the best one. In case when that produces no result, all translation siteaccesses will be
checked in the order they are configured. As this behaviour is not commonly desired, it's disabled
by default, and can be turned on per siteaccess with
``ng_cross_siteaccess_routing_prefer_translation_siteaccess`` option.

If ``excluded_uri_prefixes`` options is used on a siteaccess, it should be separately configured for
cross-siteaccess router with the corresponding Location ID. This is needed because
``excluded_uri_prefixes`` is used for matching an URL, and as such is not really usable for
generating an URL. Counterparts of the "excluded URI prefixes" for generating cross-siteaccess links
are called "external subtree roots", and they can be configured per siteaccess with
``ng_cross_siteaccess_routing_external_subtree_roots`` option. If the Location is found to be under
the configured external tree root, the link to it will be generated on the current siteaccess.

Host part of the resulting URL will always be generated if requested, but otherwise only if
necessary, meaning only if it's different from the current host. This is also valid for ``path``
function in Twig, as otherwise it would not be possible to correctly link to a Location on a
siteaccess with a different host.

All configuration options, showing defaults:

.. code-block:: yaml

    ezpublish:
        system:
            frontend:
                ng_cross_siteaccess_routing: true
                ng_cross_siteaccess_routing_prefer_translation_siteaccess: false
                ng_cross_siteaccess_routing_external_subtree_roots: []

Site API Content views
~~~~~~~~~~~~~~~~~~~~~~

Once you enable ``override_url_alias_view_action`` for a siteaccess, all your **full view** templates
and controllers will need to use Site API to keep working. They will be resolved from Site API view
configuration, available under ``ngcontent_view`` key. That means Content and Location variables
inside Twig templates will be instances of Site API Content and Location value objects, ``$view``
variable passed to your custom controllers will be an instance of Site API ContentView variable, and
so on.

If needed you can still use ``content_view`` rules. This will allow you to have both Site API
template override rules as well as original eZ Platform template override rules, so you can rewrite
your templates bit by bit. You can decide which one to use by directly rendering either
``ng_content:viewAction`` or ``ez_content:viewAction`` controller.

It's also possible to configure fallback between Site API and eZ Platform views. With it, if the
rule is not matched in one view configuration, the fallback mechanism will try to match it in the
other. Find out more about that in the following section.

.. tip::

    | View configuration is the only eZ Platform configuration regularly edited
    | by frontend developers.

For example, if using the following configuration:

.. code-block:: yaml

    ezpublish:
        system:
            frontend_group:
                ngcontent_view:
                    line:
                        article:
                            template: '@App/content/line/article.html.twig'
                            match:
                                Identifier\ContentType: article
                content_view:
                    line:
                        article:
                            template: '@App/content/line/ez_article.html.twig'
                            match:
                                Identifier\ContentType: article

Rendering a line view for an article with ``ng_content:viewAction`` would use
``@App/content/line/article.html.twig`` template, while rendering a line view for an article with
``ez_content:viewAction`` would use ``@App/content/line/ez_article.html.twig`` template.

It is also possible to use custom controllers, this is documented on
:doc:`Custom controllers reference</reference/custom_controllers>` documentation page.

.. _content_view_fallback_configuration:

Content View fallback
~~~~~~~~~~~~~~~~~~~~~

You can configure fallback between Site API and eZ Platform views. Fallback can be controlled
through two configuration options (showing default values):

.. code-block:: yaml

    ezpublish:
        system:
            frontend_group:
                ng_fallback_to_secondary_content_view: false
                ng_fallback_without_subrequest: false

- ``ng_fallback_to_secondary_content_view``

    With this option you control whether **automatic fallback** will be used. By default, automatic
    fallback is disabled. Secondary content view means the fallback can be used both from Site API
    to eZ Platform views, and from eZ Platform to Site API content views. Which one will be used is
    defined by ``override_url_alias_view_action`` configuration documented above.

- ``ng_fallback_without_subrequest``

    With this option you can control whether the fallback will use a subrequest (default), or Twig
    functions that can render content view without a subrequest. That applies both to automatic and
    manually configured fallback. Rendering views without a subrequest is faster in debug mode,
    where profiling is turned on. Depending on the number of views used on a page, performance
    improvement when not using subrequest can be significant.

.. warning::

    Because of reverse siteaccess matching limitations, when ``ng_fallback_without_subrequest`` is
    turned off, links in the preview in the admin UI will not be correctly generated. To work around
    that problem, turn the option on.

.. note::

    For backward compatibility reasons, ``ng_fallback_to_secondary_content_view`` and
    ``ng_fallback_without_subrequest`` are turned off, but in next major release that will be
    reversed by default.

.. note::

    When fallback is enabled default templates for the primary view will not be used. Otherwise the
    fallback would never happen, because the primary view would always use the default templates
    instead of falling back to the secondary view. Similarly, when falling back to the secondary
    view, if its view configuration doesn't match, the default template of the secondary view will
    be rendered.


You can also configure fallback manually, per view. This is done by configuring a view to render one
of two special templates, depending if the fallback is from Site API to eZ Platform views or the
opposite.

- ``@NetgenEzPlatformSiteApi/content_view_fallback/to_ez_platform.html.twig``

  This template is used for fallback from Site API to eZ Platform views. In the following example
  it's used to configure fallback for ``line`` view of ``article`` ContentType:

  .. code-block:: yaml

      ezpublish:
          system:
              frontend_group:
                  ngcontent_view:
                      line:
                          article:
                              template: '@NetgenEzPlatformSiteApi/content_view_fallback/to_ez_platform.html.twig'
                              match:
                                  Identifier\ContentType: article

- ``@NetgenEzPlatformSiteApi/content_view_fallback/to_site_api.html.twig``

  This template is used for fallback from eZ Platform to Site API views. In the following example
  it's used to configure fallback for all ``full`` views:

  .. code-block:: yaml

      ezpublish:
          system:
              frontend_group:
                  content_view:
                      full:
                          catch_all:
                              template: '@NetgenEzPlatformSiteApi/content_view_fallback/to_site_api.html.twig'
                              match: ~

Redirections
~~~~~~~~~~~~

With Site API, it's also possible to configure redirects directly from the view configuration.
You can set up temporary or permanent redirect to either ``Content``, ``Location``, ``Tag``, Symfony route or any full url.

For the target configuration you can use expression language, meaning it is easily possible to redirect, for example,
to the parent of the current location, or to the named object.

Example configuration:

.. code-block:: yaml

    ezpublish:
        system:
            frontend_group:
                ngcontent_view:
                    container:
                        redirect:
                            target: "@=location.parent"
                            target_parameters:
                                foo: bar
                            permanent: false
                        match:
                            Identifier\ContentType: container
                    article:
                        redirect:
                            target: "@=namedObject.getLocation('homepage')"
                            target_parameters:
                                foo: bar
                                siteaccess: cro
                            permanent: true
                            absolute: true
                        match:
                            Identifier\ContentType: article
                    category:
                        redirect:
                            target: '@=location.getChildren(1)[0]'
                            permanent: true
                        match:
                            Identifier\ContentType: category
                    news:
                        redirect:
                            target: 'login'
                            target_parameters:
                                foo: bar
                            permanent: false
                        match:
                            Identifier\ContentType: news
                    blog:
                        redirect:
                            target: 'https://netgen.io'
                        match:
                            Identifier\ContentType: blog

There also shortcuts available for simplified configuration:

.. code-block:: yaml

    ezpublish:
        system:
            frontend_group:
                ngcontent_view:
                    container:
                        temporary_redirect: "@=namedObject.getTag('running')"
                        match:
                            Identifier\ContentType: container
                    category:
                        permanent_redirect: "@=content.getFieldRelation('internal_redirect')"
                        match:
                            Identifier\ContentType: container

.. note::

    Configuration of named objects is documented in more detail below.

Shortcut functions are available for accessing each type of named object directly:

- ``namedContent(name)``

    Provides access to named Content.

- ``namedLocation(name)``

    Provides access to named Location.

- ``namedTag(name)``

    Provides access to named Tag.

.. _named_object_configuration:

Named objects
~~~~~~~~~~~~~

Named objects feature provides a way to configure specific objects (``Content``, ``Location`` and
``Tag``) by name and ID, and a way to access them by name from PHP, Twig and Query Type
configuration.

Example configuration:

.. code-block:: yaml

    netgen_ez_platform_site_api:
        system:
            frontend_group:
                named_objects:
                    content:
                        certificate: 42
                        site_info: 'abc123'
                    location:
                        homepage: 2
                        articles: 'zxc456'
                    tag:
                        categories: 24
                        colors: 'bnm789'

From the example, ``certificate`` and ``site_info`` are names of Content objects, ``homepage`` and
``articles`` are names of Location objects and ``categories`` and ``colors`` are names of Tag
objects. The example also shows it's possible to use both a normal ID (integer) or remote ID
(string). In fact, it shows a short syntax, where the type of ID is inferred from the type, while
full syntax equivalent to the above would be:

.. code-block:: yaml

    netgen_ez_platform_site_api:
        system:
            frontend_group:
                named_objects:
                    content:
                        certificate:
                            id: 42
                        site_info:
                            remote_id: 'abc123'
                    location:
                        homepage:
                            id: 2
                        articles:
                            remote_id: 'zxc456'
                    tag:
                        categories:
                            id: 24
                        colors:
                            remote_id: 'bnm789'

Accessing named objects
-----------------------

- access from PHP is :ref:`documented on the Services page<named_object_php>`
- access from Twig is :ref:`documented on Templating page<named_object_template>`
- access from Query Type configuration is :ref:`documented on Query Types page<named_object_query_types>`

.. _content_field_inconsistencies:

Content Field inconsistencies
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes when the content model is changed or for any reason the data is not consistent, it can
happen that some Content Fields are missing. In case of content model change that is a temporary
situation lasting while the data is being updated in the background. But even in the case of
inconsistent database, typically you do not want that to result in site crash.

To account for this Site API provides the following semantic configuration:

.. code-block:: yaml

    netgen_ez_platform_site_api:
        system:
            frontend_group:
                fail_on_missing_fields: true
                render_missing_field_info: false

By default ``fail_on_missing_fields`` is set to ``%kernel.debug%`` container parameter, which means
accessing a nonexistent field in ``dev`` environment will fail and result in a ``RuntimeException``.

On the other hand, when not in debug mode (in ``prod`` environment), the system will not crash, but
will instead return a special ``Surrogate`` type field, which always evaluates as empty and renders
to an empty string. In this case, a ``critical`` level message will be logged, so you can find and
fix the problem.

Second configuration option ``render_missing_field_info`` controls whether ``Surrogate`` field will
render as an empty string or it will render useful debug information. By default its value is
``false``, meaning it will render as an empty string. That behavior is also what you should use in
the production environment. Setting this option to ``true`` can be useful in debug mode, together
with setting ``fail_on_missing_fields`` to ``false``, as that will provide a visual cue about the
missing field without the page crashing and without the need to go into the web debug toolbar to
find the logged message.

.. note::

    You can configure both ``render_missing_field_info`` and ``fail_on_missing_fields`` per
    siteaccess or siteaccess group.
