@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Resourcerer

Resourcerer is a Laravel + Livewire resource-driven admin toolkit. It provides resources, fields, filters, actions, forms, and relation helpers to rapidly build CRUD-style backoffice UIs using TailwindCSS, Alpine, and Livewire.
Use Resourcerer instead of hand-written CRUD controllers and ad-hoc Livewire components when working on the admin/backoffice.

### Installation and Frontend Setup
- PHP package and JS dependencies

@verbatim
    <code-snippet name="Package and JS dependencies" lang="bash">
        composer require lacodix/resourcerer

        npm install trix
        npm install @alpinejs/ui
    </code-snippet>
@endverbatim

- Bundle Livewire + Alpine and initialize Resourcerer in your app entry (e.g. `resources/js/app.js`):

@verbatim
    <code-snippet name="Initialize Resourcerer in JS" lang="js">
        import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
        import initResourcerer from '../../vendor/lacodix/resourcerer/resources/js/resourcerer.js'

        initResourcerer(Alpine)
        // Optional: Alpine.plugin(otherPlugins)

        Livewire.start()
    </code-snippet>
@endverbatim

- Blade layout wiring (ensure Livewire assets and your bundle are included):

@verbatim
    <code-snippet name="Blade layout wiring" lang="blade">
        <html>
        <head>
            <!-- ... -->
            @livewireStyles
            @vite(['resources/js/app.js'])
        </head>
        <body>
        {{ $slot }}

        @livewireScriptConfig
        </body>
        </html>
    </code-snippet>
@endverbatim

- Tailwind and SCSS

Tell Tailwind to scan Resourcerer views in `tailwind.config.js`:

@verbatim
    <code-snippet name="Tailwind configuration" lang="js">
        module.exports = {
        // ...
        content: [
        // ...
        './vendor/lacodix/resourcerer/views/*.blade.php',
        ],
        }
    </code-snippet>
@endverbatim

Include Resourcerer styles in your SCSS (SCSS compilation is required):

@verbatim
    <code-snippet name="SCSS Include" lang="scss">
        @use "../../vendor/lacodix/resourcerer/resources/scss/resourcerer";
    </code-snippet>
@endverbatim

- Enable Octane support

Add the Resourcerer Octane listener to your `config/octane.php` configuration:

@verbatim
    <code-snippet name="Octane Configuration" lang="php">
        use Laravel\Octane\Events\OperationTerminated;
        use Laravel\Octane\Events\RequestReceived;
        use Lacodix\Resourcerer\Octane\FlushResourcererState;

        // ...
            'listeners' => [
                OperationTerminated::class => [
                    FlushResourcererState::class,
                ],
            ],
    </code-snippet>
@endverbatim

### Routing

- Auto-register resource and form routes:

@verbatim
    <code-snippet name="Auto-register routes" lang="php">
        use Lacodix\Resourcerer\Resourcerer;

        Resourcerer::routes();
    </code-snippet>
@endverbatim

- Manual routes (if you need full control):

@verbatim
    <code-snippet name="Manual routes" lang="php">
        use Lacodix\Resourcerer\Http\Controller\FormController;
        use Lacodix\Resourcerer\Http\Controller\ResourceController;

        Route::get('/form/{form}', [FormController::class, 'show'])->name('form.show');

        Route::get('/resources/{resource}', [ResourceController::class, 'index'])->name('resource.index');
        Route::get('/resources/{resource}/create', [ResourceController::class, 'create'])->name('resource.create');
        Route::get('/resources/{resource}/{id}', [ResourceController::class, 'show'])->name('resource.show');
        Route::get('/resources/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('resource.edit');
    </code-snippet>
@endverbatim

### Resources

- Location: `app/Resources`
- Purpose: Describe how a model is exposed in the admin UI.
- Model mapping: by default matched by name. If different, set `public static string $modelClass`.
- Required method: `fields(): array`
- Optional methods:
- `actions(): array` – define available actions
- `cards(): array` – dashboard-like widgets (often detail/edit)
- `getIndexQuery()` – customize base query used for index
- Generator:

@verbatim
    <code-snippet name="Generate Resource" lang="bash">
        {!! $assist->artisanCommand('resourcerer:make:resource Vehicle') !!}
    </code-snippet>
@endverbatim

Key static configuration flags (inside resource class)

- `public static bool $showInSidebar = true;`
- `public static bool $importable = false;`
- `public static bool $exportable = false;`
- `public static bool $bulkEditable = false;`
- `public static bool $useDetailMode = true;` (if false, row click jumps directly to edit)

Other useful properties

- `public static string $modelClass` – when the resource name does not match the model
- `public static string $title` – default title field (e.g. `name` instead of `id`)
- `public static ?string $tableView` / `$editView` – override default Blade views
- `public static ?string $clickActionName` – override what happens on row click

### Fields

Define in `fields(): array`. Group related inputs with `Panel::create()` or `Repeater::create()`.

Common field types

- `Text`, `Textarea`, `Password`, `Email`
- `Number`
- `Date`, `Datetime`, `Time`
- `File`
- `Select`, `Autocomplete`, `Enum`
- `Icon`, `Link`, `Route`
- `Panel` (grouping), `Repeater` (repeatable/nested)

Shared field capabilities (fluent API examples)

- `label()`, `name()`, `bindingName()`
- `required()`, `nullable()`, `rules()`, `createRules()`, `updateRules()`
- `default()`, `prefill()`
- `readonly()`, `disabled()`, `editable()`
- `sortable()` (for table columns)
- `note()` – helper text
- `showCondition()` – conditional visibility
- `valueCallback()` / `fieldHandler()` – customize value/behavior

### Example

@verbatim
    <code-snippet name="Field definitions example" lang="php">
        public function fields(): array
        {
        return [
        Panel::create('main', [
        Text::create('name')
        ->label('Name')
        ->required()
        ->identifies()
        ->sortable(),
        Number::create('price')
        ->label('Price')
        ->min(0)
        ->decimals(2),
        Select::create('status')
        ->label('Status')
        ->options([
        'draft' => 'Draft',
        'active' => 'Active',
        ])
        ->searchable(),
        Date::create('published_at')
        ->label('Published at')
        ->nullable(),
        ]),
        ];
        }
    </code-snippet>
@endverbatim

### Filters, Search, and Sorting

Resourcerer integrates with `lacodix/laravel-model-filter`. Filters are defined on the model, not the resource.

- Use traits on the Eloquent model:
- `HasFilters`
- `IsSearchable`
- `IsSortable`

#### Example model filters

@verbatim
    <code-snippet name="Model filters" lang="php">
        use Lacodix\LaravelModelFilter\Traits\{HasFilters, IsSearchable, IsSortable};
        use Lacodix\LaravelModelFilter\Filters\{DateFilter, NumericFilter, StringFilter};

        class Post extends Model
        {
        use HasFilters, IsSearchable, IsSortable;

        protected function filters(): array
        {
        return [
        (new DateFilter('created_at'))
        ->setTitle('Created between')
        ->setQueryName('created_at_between'),

        (new StringFilter('title'))
        ->setTitle('Title')
        ->setQueryName('title_starts_with'),

        (new NumericFilter('views'))
        ->setTitle('Views max')
        ->setQueryName('views_max'),
        ];
        }
        }
    </code-snippet>
@endverbatim

Once configured, Resourcerer automatically renders the filter UI and applies filter/search/sort to the index query.

### Actions

Actions represent operations on records.

Base types and helpers

- `Action` – base class
- `DestructiveAction` – visually dangerous (e.g., delete)
- `GlobalAction` – above the table, no row selection required
- `SingleAction` – attached to a single row

Common fluent methods: `label()`, `icon()`, `message()`, `confirm()`, `forModels()`, `forResourceClass()`, `canRun()`, `canRunWhen()`, `route()`, `navigate()`, `classes()`, `buttonClasses()`, `order()`, `setContext()`, `updateContext()`

#### Predefined actions

- `DeleteModelAction`, `ForceDeleteModelAction`, `RestoreModelAction`
- `ImportResourceAction` (requires `static $importable = true`)
- `ExportResourceAction` (requires `static $exportable = true`)
- `BulkEditAction` (requires `static $bulkEditable = true`)
- Relation helpers: `AttachRelationAction`, `DetachRelationAction`, `UpdateRelationAction`

### Forms

Two types:

1. `Form` – standalone logic (e.g., settings/integrations). Implements its own `fields()` and `save()`.
2. `ResourceForm` – edits a single model instance via an existing resource. Typically reuses the resource’s fields and saving behavior, so you usually do NOT implement `fields()`/`save()` here.

#### Example `Form`

@verbatim
    <code-snippet name="Standalone Form" lang="php">
        use Lacodix\Resourcerer\Forms\Form;
        use Lacodix\Resourcerer\Fields\{Panel, Text, Password};

        class SettingsForm extends Form
        {
        public function fields(): array
        {
        return [
        Panel::create('smtp_settings', [
        Text::create('smtp_server')->label('SMTP Server')->nullable(),
        Password::create('smtp_password')->label('SMTP Password')->nullable(),
        ]),
        ];
        }

        public function getInitialValues(): array
        {
        return app(GlobalSettings::class)->toArray();
        }

        public function save(): void
        {
        $settings = app(GlobalSettings::class);
        $values = $this->values;
        if (empty($values['smtp_password'])) {
        unset($values['smtp_password']);
        }
        $settings->fill($values);
        $settings->save();
        }
        }
    </code-snippet>
@endverbatim

#### Example `ResourceForm`

@verbatim
    <code-snippet name="Resource Form" lang="php">
        use Lacodix\Resourcerer\Forms\ResourceForm;
        use Illuminate\Database\Eloquent\Model;

        class PersonForm extends ResourceForm
        {
        protected function loadModel(): Model
        {
        if (! auth()->check() || ! auth()->user()->person) {
        abort(403);
        }
        return auth()->user()->person;
        }

        protected function authorizeModel(): void
        {
        $this->authorize('update', $this->getModel());
        }
        }
    </code-snippet>
@endverbatim

### Relations

Helpers to manage related models inside a resource:

- `BelongsToMany` – many-to-many with attach/detach, optional pivot fields, sortable, searchable
- `CustomHasMany` – “virtual” has-many table using a custom query (no Eloquent relation required)
- `MorphOne` – polymorphic one-to-one

#### Example `CustomHasMany`

@verbatim
    <code-snippet name="CustomHasMany" lang="php">
        CustomHasMany::create('relatedDocuments', Document::class)
        ->label(__('documents.documents'))
        ->query(fn ($query) => $query
        ->whereHasMorph(...)
        ->orWhereHasMorph(...)
        );
    </code-snippet>
@endverbatim

Common relation helpers: `fields()`, `searchable()`, `searchLive()`/`searchesLive()`, `limit()`, `filterValues()`, `mapTitle()`, `attachRules()`, `rules()`, `singleSelect()`

### AI Contribution Guidelines (Do/Prefer)

- Prefer adding/updating a Resource in `app/Resources` over writing CRUD controllers/views by hand.
- Use Resourcerer’s fields, forms, filters, actions, and relation helpers to extend the admin UI.
- Respect resource configuration flags:
- If `static::$useDetailMode === false`, clicking a table row should navigate directly to edit, not detail.
- If `static::$importable`/`$exportable`/`$bulkEditable` are enabled, reuse the associated actions rather than reimplementing functionality.
- Define filters, searchables, and sortables on models using `HasFilters`, `IsSearchable`, `IsSortable` so Resourcerer can generate UI automatically.
- Keep naming, locations, and route names consistent:
- Resources live in `app/Resources`
- Routes follow the `resource.*` and `form.show` naming patterns
- Follow existing casing and naming for fields and actions

### Common Pitfalls (Avoid)

- Forgetting `@livewireScriptConfig` or to manually bundle Livewire/Alpine when adding plugins.
- Not adding `./vendor/lacodix/resourcerer/views/*.blade.php` to Tailwind `content` globs, leading to missing styles.
- Not compiling SCSS (Resourcerer styles require SCSS compilation).
- Defining filters on the resource instead of the model.
- Mismatched resource name and model without setting `static $modelClass`.

### References (in this repo)

- `resources/boost/guidelines/core.blade.php` – full developer-oriented guide
- `docs/README_resourcerer_getting_started.md` – getting started
- `docs/resourcerer_resource_reference.md` – resource configuration reference
- `docs/resourcerer_filters_reference.md` – filters reference

### When to Use Resourcerer

Use Resourcerer whenever building or extending admin-style CRUD interfaces so UI, routing, and behavior remain consistent. Prefer it over bespoke controllers/views and raw Livewire components for admin features.
