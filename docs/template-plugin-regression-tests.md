# Template Plugin Regression Tests

This document explains, in detail, what each review-driven regression test is doing and why it exists.

It is written for readers who are not already familiar with the Zen Cart test framework.

For each test, this document covers:

- where the test lives
- which production file it is targeting
- how the test framework executes it
- what the fixture data looks like
- what each step in the test method is doing
- what condition the test is asserting
- what a pass means
- what a failure means

The tests covered here are:

1. `TemplateSelectTest::testEditFormPreselectsTemplateAssignedToEditedLanguageRow`
2. `BaseLanguageLoaderTemplateChainTest::testChildPluginTemplateResolvesItsOwnLanguageOverrideFile`
3. `BaseLanguageLoaderTemplateChainTest::testChildPluginTemplateLoadsParentPluginLanguageOverridesInInheritanceOrder`
4. `BaseLanguageLoaderTemplateChainTest::testChildPluginTemplateLoadsParentPluginRootLanguageFiles`
5. `AdminInitTemplatesTest::testAdminInitTemplatesLoadsActiveTemplateWhenTemplateDirStartsEmpty`

## Test Framework Background

Before looking at the individual tests, it helps to understand the two styles of tests used here.

### Feature tests

Feature tests simulate an actual request flow through Zen Cart. They are used when the behavior under test depends on:

- HTTP request handling
- admin login
- database-backed screens
- rendered HTML forms

In this set, `TemplateSelectTest` is a feature test.

It extends:

- [zcInProcessFeatureTestCaseAdmin](/home/wilt/Projects/zencart/not_for_release/testFramework/Support/zcInProcessFeatureTestCaseAdmin.php:1)

That base class provides helpers like:

- `submitAdminLogin(...)`
- `visitAdminCommand(...)`
- `submitAdminForm(...)`

Those helpers execute admin requests in-process and return a `FeatureResponse` object.

`FeatureResponse` provides helpers like:

- `assertOk()`
- `assertSee(...)`
- `formDefaults(...)`
- `formAction(...)`

The important one for this test is:

- `formDefaults('templateselect')`

That method parses the HTML form and returns the default field values that would be submitted if the user clicked the form’s submit button without changing anything.

### Unit tests

Unit tests do not run a full request cycle. They usually:

- create a minimal fake filesystem
- mock database results
- instantiate one class directly
- call one method
- assert on the returned value

In this set:

- `BaseLanguageLoaderTemplateChainTest` is a unit test
- `AdminInitTemplatesTest` is a unit test

These tests rely on lightweight fixtures and mocks rather than a complete Zen Cart runtime.

## 1. Admin Template Selection Test

### File

- [not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:1)

### Production file under test

- [admin/template_select.php](/home/wilt/Projects/zencart/admin/template_select.php:1)

### Specific problem

When the admin user edits a row in the template selection screen, the template dropdown is supposed to preselect the template already assigned to that row.

Instead, the current code uses the active template:

- [admin/template_select.php](/home/wilt/Projects/zencart/admin/template_select.php:234)

That means the form default is driven by:

```php
$templateSelect->getActiveTemplateDir()
```

instead of the row being edited:

```php
$tInfo->template_dir
```

### Why this matters

If the admin opens a non-default language row that points to a different template than the active storefront template, the edit form shows the wrong selected option.

If the admin clicks `Update` without noticing the mismatch, the row can be overwritten with the active template.

### Line-by-line walkthrough

#### Class definition

- [TemplateSelectTest.php:12](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:12)

```php
class TemplateSelectTest extends zcInProcessFeatureTestCaseAdmin
```

This means:

- the test runs as an admin feature test
- it can make in-process admin requests
- it can log into admin
- it can inspect rendered HTML responses

#### Separate process settings

- [TemplateSelectTest.php:14](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:14)

```php
protected $runTestInSeparateProcess = true;
protected $preserveGlobalState = false;
```

This is important because feature tests manipulate:

- globals
- session state
- DB state
- constants

Running them in a separate process prevents contamination across tests.

#### Seeder

- [TemplateSelectTest.php:18](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:18)

```php
$this->runCustomSeeder('StoreWizardSeeder');
```

This loads the baseline database state needed for admin login and template-related behavior.

Without this, the admin environment might not exist in a predictable form.

#### Admin login

- [TemplateSelectTest.php:20](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:20)

```php
$this->submitAdminLogin([
    'admin_name' => 'Admin',
    'admin_pass' => 'password',
])->assertOk()
  ->assertSee('Admin Home');
```

This does three things:

1. visits the admin login page
2. posts the login credentials
3. asserts the response is successful and the admin home page is visible

At this point the test is authenticated as an admin user.

#### Legacy DB connection

- [TemplateSelectTest.php:26](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:26)

```php
$db = $this->bootstrapLegacyDbConnection();
```

The feature harness itself handles requests, but this helper is used so the test can directly manipulate `TABLE_TEMPLATE_SELECT` before loading the screen being tested.

#### Resetting template rows

- [TemplateSelectTest.php:27](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:27)

```php
$db->Execute("DELETE FROM " . TABLE_TEMPLATE_SELECT);
```

This wipes existing rows so the test is not dependent on whatever the seeder inserted for templates.

That is important because the bug is about which row is being edited versus which template is active. The test needs tight control over both.

#### Inserting the default row

- [TemplateSelectTest.php:28](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:28)

```php
INSERT INTO template_select (template_dir, template_language)
VALUES ('template_default', 0)
```

This establishes the default language template row.

Language `0` is the fallback/default template assignment in Zen Cart.

#### Inserting the row being edited

- [TemplateSelectTest.php:34](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:34)

```php
INSERT INTO template_select (template_dir, template_language)
VALUES ('responsive_classic', 999)
```

This creates a second row, for an arbitrary non-default language id `999`.

The specific numeric language id is not important here. What matters is:

- it is a different row from the default
- it points to `responsive_classic`

That gives the test a row whose assigned template differs from the default row.

#### Capturing the inserted id

- [TemplateSelectTest.php:39](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:39)

```php
$templateId = (int)$db->insert_ID();
```

The test needs the primary key so it can open the edit form for exactly this row.

#### Visiting the edit screen

- [TemplateSelectTest.php:41](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:41)

```php
$page = $this->visitAdminCommand('template_select&tID=' . $templateId . '&action=edit')
```

This loads the same admin page a user would see after clicking the edit action for that row.

The important query parameters are:

- `tID=<row id>`
- `action=edit`

#### Basic page assertions

- [TemplateSelectTest.php:42](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:42)

```php
->assertOk()
->assertSee('responsive_classic');
```

These assertions confirm:

- the page loaded successfully
- the row being rendered is the one we expect

This does not yet prove the correct dropdown option is selected. It only proves the page includes the right template name somewhere in the rendered content.

#### The key assertion

- [TemplateSelectTest.php:45](/home/wilt/Projects/zencart/not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php:45)

```php
$page->formDefaults('templateselect')['ln'] ?? null
```

This asks the test framework:

- find the form named `templateselect`
- parse its default values
- return the default value for the field named `ln`

`ln` is the template-selection dropdown in the admin edit form.

The test expects:

```php
'responsive_classic'
```

That is the existing template assigned to the row being edited.

### What a passing result means

A pass means:

- the edit form’s dropdown is using the row’s stored template
- an admin who opens the edit screen sees the correct preselected option

### What the current failure means

On the current branch, this test fails because the default value is the active template, not the row’s assigned template.

That confirms the form is built from the wrong source of truth.

## 2. Child Plugin Template: Own Override File

### File

- [not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:1)

### Production file under test

- [includes/classes/ResourceLoaders/BaseLanguageLoader.php](/home/wilt/Projects/zencart/includes/classes/ResourceLoaders/BaseLanguageLoader.php:1)

### Why this test exists

This test is the control case for the more interesting inheritance failures.

Before asserting that parent plugin template language files are missing, the suite needs to prove that the child plugin template can at least resolve its own language file.

If this test failed, the fixture or test harness would be suspect and the inheritance-focused failures would be much harder to interpret.

### Fixture setup in `setUp()`

The `setUp()` method creates a temporary filesystem that mimics a very small catalog install.

#### Temporary root

- [BaseLanguageLoaderTemplateChainTest.php:24](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:24)

```php
$this->fixtureRoot = sys_get_temp_dir() . '/zencart-language-loader-' . uniqid('', true);
```

Every test run gets a unique temp directory.

#### Template directories

The test creates:

- `includes/templates/template_default`
- `zc_plugins/BaseTheme/v1.0.0/catalog/includes/templates/base_theme`
- `zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme`

That models:

- the core fallback template
- a parent plugin template
- a child plugin template

#### Language directories

The test creates:

- `includes/languages/english/template_default`
- `zc_plugins/BaseTheme/.../includes/languages/english/base_theme`
- `zc_plugins/ChildTheme/.../includes/languages/english/child_theme`
- `zc_plugins/BaseTheme/.../includes/languages/base_theme`

These cover two different language-file patterns:

- `english/<template>/lang.example.php`
- `<template>/lang.english.php`

#### Template manifests

The test writes two plugin manifests:

- `BaseTheme` with template key `base_theme`
- `ChildTheme` with template key `child_theme`

And importantly:

- `child_theme` declares `baseTemplate => base_theme`
- `base_theme` declares `baseTemplate => template_default`

That is what makes this an inheritance-chain fixture instead of just three unrelated templates.

#### Language files

The test writes:

- `template_default/lang.example.php`
- `base_theme/lang.example.php`
- `child_theme/lang.example.php`
- global `lang.english.php`
- parent plugin root `base_theme/lang.english.php`

Each file contains a unique value so that if the loader finds it, the path can be unambiguously identified.

#### Active template DB record

The call to:

- [BaseLanguageLoaderTemplateChainTest.php:72](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:72)

```php
$this->instantiateQfr([... 'template_dir' => 'child_theme' ...]);
```

mocks the `TABLE_TEMPLATE_SELECT` lookup so the active template is `child_theme`.

This is important because `TemplateSelect` and `TemplateResolver` rely on that data.

### Testable wrapper class

At the bottom of the file, the test defines:

- `TestableBaseLanguageLoader`

This is a small subclass that exposes protected methods from `BaseLanguageLoader` as public methods for testing:

- `publicFindTemplateLanguageOverrideFile(...)`
- `publicGetTemplateLanguageOverrideFiles(...)`
- `publicGetTemplateFirstLanguageFiles(...)`

This is a common unit-test pattern when the real behavior is implemented in protected helpers.

### Test method walkthrough

#### Constructing the loader

- [BaseLanguageLoaderTemplateChainTest.php:88](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:88)

```php
$loader = new TestableBaseLanguageLoader($this->fixtureRoot, 'child_theme');
```

This tells the loader:

- the catalog root is the temp fixture
- the active template key is `child_theme`

#### Calling the lookup method

- [BaseLanguageLoaderTemplateChainTest.php:90](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:90)

```php
$file = $loader->publicFindTemplateLanguageOverrideFile(
    $this->fixtureRoot . '/includes/languages',
    'english',
    'lang.example.php'
);
```

This asks:

- starting from the English language root
- find the applicable template override for `lang.example.php`

#### Assertion

- [BaseLanguageLoaderTemplateChainTest.php:96](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:96)

The expected result is the child template’s own override file:

```text
zc_plugins/ChildTheme/v1.0.0/catalog/includes/languages/english/child_theme/lang.example.php
```

### What a pass means

A pass means the loader can at least resolve the active child plugin template’s own override file.

### Why this test is important

This does not test the bug directly. It verifies that the child-template side of the fixture works, so the later inheritance tests can fail for the right reason.

## 3. Child Plugin Template: Parent Plugin Override Chain

### Test name

- `testChildPluginTemplateLoadsParentPluginLanguageOverridesInInheritanceOrder`

### Problem being tested

This test checks whether language override files from a parent plugin template are included when a child plugin template inherits from it.

The production expectation is:

- `template_default` should be part of fallback
- `base_theme` should be part of inherited plugin template lookup
- `child_theme` should be the final child override

### Line-by-line walkthrough

#### Loader construction

- [BaseLanguageLoaderTemplateChainTest.php:101](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:101)

```php
$loader = new TestableBaseLanguageLoader($this->fixtureRoot, 'child_theme');
```

The active template is again `child_theme`.

#### Lookup call

- [BaseLanguageLoaderTemplateChainTest.php:103](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:103)

```php
$files = $loader->publicGetTemplateLanguageOverrideFiles(
    $this->fixtureRoot . '/includes/languages',
    'english',
    'lang.example.php'
);
```

This method is different from the previous test:

- it returns the full ordered list of matching files
- not just the first match

That makes it useful for checking inheritance order.

#### Expected order

- [BaseLanguageLoaderTemplateChainTest.php:109](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:109)

The expected list is:

1. `includes/languages/english/template_default/lang.example.php`
2. `zc_plugins/BaseTheme/.../english/base_theme/lang.example.php`
3. `zc_plugins/ChildTheme/.../english/child_theme/lang.example.php`

The meaning of that order is:

- start with the core fallback
- then load the parent plugin template
- then load the child plugin template

That is what inheritance means in this context.

### What a pass means

A pass means parent plugin template language override files are part of the inheritance chain.

### What the current failure means

On the current branch, the parent and child plugin entries do not appear in the returned list.

That means parent plugin template language roots are not being included in override discovery the way the inheritance model implies they should be.

## 4. Child Plugin Template: Parent Plugin Root Language File

### Test name

- `testChildPluginTemplateLoadsParentPluginRootLanguageFiles`

### Problem being tested

This is similar to the previous test, but it exercises a different language-file pattern:

- `lang.english.php`

instead of:

- `english/<template>/lang.example.php`

That matters because the loader has different helper paths for these two cases.

### Line-by-line walkthrough

#### Loader construction

- [BaseLanguageLoaderTemplateChainTest.php:117](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:117)

Again the loader is initialized for `child_theme`.

#### Lookup call

- [BaseLanguageLoaderTemplateChainTest.php:119](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:119)

```php
$files = $loader->publicGetTemplateFirstLanguageFiles(
    'includes/languages',
    'lang.english.php'
);
```

This method is specifically checking the “template-first” language file chain.

#### Expected files

- [BaseLanguageLoaderTemplateChainTest.php:124](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php:124)

The expected result is:

1. the global fallback file:
   - `includes/languages/lang.english.php`
2. the parent plugin template root file:
   - `zc_plugins/BaseTheme/.../includes/languages/base_theme/lang.english.php`

The child plugin template does not have its own root `lang.english.php` fixture, so the test is isolating whether the parent file is discovered.

### What a pass means

A pass means the root-level language file lookup also honors parent plugin template inheritance.

### What the current failure means

If this fails, parent plugin template root language files are not part of inheritance, even though the child manifest explicitly declares a plugin-template parent.

## 5. Admin Init Template Resolution Test

### File

- [not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:1)

### Production file under test

- [admin/includes/init_includes/init_templates.php](/home/wilt/Projects/zencart/admin/includes/init_includes/init_templates.php:1)

### Problem being tested

The code currently says:

```php
if (!empty($template_dir)) {
    $templateSelect = new TemplateSelect();
    $template_dir = $templateSelect->getActiveTemplateDir();
}
```

That appears backwards.

The suspected intended behavior is:

- if `$template_dir` is empty
- then fetch the active template from `TemplateSelect`

The current code only fetches the active template when `$template_dir` is already non-empty.

### Why this needs a special test style

This file is not a small standalone class method. It is a bootstrap include file that:

- expects certain constants to exist
- expects certain classes to exist
- creates definitions like `DIR_WS_TEMPLATE`
- includes `template_func.php`

So this test does not instantiate a single class directly. Instead, it constructs just enough runtime context for the include file to execute.

### Separate process requirement

- [AdminInitTemplatesTest.php:12](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:12)

```php
protected $runTestInSeparateProcess = true;
protected $preserveGlobalState = false;
```

This is necessary because the test defines constants like:

- `IS_ADMIN_FLAG`
- `DIR_FS_CATALOG`
- `TABLE_TEMPLATE_SELECT`

Those would be hard to isolate safely inside a shared PHP process.

### Fixture setup in `setUp()`

#### Repo root detection

- [AdminInitTemplatesTest.php:20](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:20)

The test computes the repository root so it can include real production files from the checkout.

#### Loading production dependencies

- [AdminInitTemplatesTest.php:22](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:22)

The test includes:

- `zen_define_default.php`
- `class.base.php`
- `query_factory.php`
- `TemplateDto.php`
- `TemplateSelect.php`
- `TemplateResolver.php`

These are the minimum dependencies needed by `admin/includes/init_includes/init_templates.php`.

#### Temporary catalog fixture

The test creates a temp filesystem with:

- `includes/templates/template_default`
- `includes/templates/admin_template_test`
- `includes/classes`

#### Template info files

It writes `template_info.php` for:

- `template_default`
- `admin_template_test`

This is necessary because `TemplateResolver` will not recognize a template without a readable `template_info.php`.

#### Stub `template_func`

- [AdminInitTemplatesTest.php:41](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:41)

The production init file requires:

```php
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'template_func.php';
```

Rather than loading the real storefront class hierarchy, the test writes a tiny stub:

```php
class template_func
{
    public function __construct(public string $directory)
    {
    }
}
```

That is enough for the bootstrap file to instantiate it without error.

### Test method walkthrough

#### Defining required constants

- [AdminInitTemplatesTest.php:60](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:60)

The test defines all the constants the admin init file expects, including:

- `IS_ADMIN_FLAG`
- `DIR_FS_CATALOG`
- `DIR_WS_CLASSES`
- `TABLE_TEMPLATE_SELECT`
- `CHARSET`
- `HEADER_TITLE_TOP`
- `TEXT_ADMIN_TAB_PREFIX`
- `STORE_NAME`

This is the minimal bootstrap contract needed for the include file.

#### Session and request setup

- [AdminInitTemplatesTest.php:69](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:69)

```php
$_SESSION['languages_id'] = 0;
$_GET = [];
```

This provides the language context used by `TemplateSelect`.

#### Mocking the DB

- [AdminInitTemplatesTest.php:72](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:72)

The test creates a `queryFactory` mock and makes `Execute()` return `queryFactoryResult` objects.

This is important because `TemplateSelect` and `TemplateResolver` expect the legacy DB layer’s normal return types.

The mocked `TABLE_TEMPLATE_SELECT` result is:

```php
[
    'template_id' => 1,
    'template_dir' => 'admin_template_test',
    'template_language' => 0,
    'template_settings' => null,
]
```

That means:

- the active template in the mock database is `admin_template_test`

The plugin-control query returns an empty result set, which is fine for this test because plugin loading is not the behavior under test.

#### Starting with an empty `$template_dir`

- [AdminInitTemplatesTest.php:98](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:98)

```php
$template_dir = '';
```

This is the key setup condition.

The entire purpose of the test is to ask:

- when admin init starts with an empty `$template_dir`
- does it correctly load the active template from `TemplateSelect`

#### Including the real bootstrap file

- [AdminInitTemplatesTest.php:100](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:100)

```php
include $this->repoRoot . 'admin/includes/init_includes/init_templates.php';
```

This executes the real production code under test.

The test is not reimplementing the logic. It is executing the actual file.

#### Assertions

- [AdminInitTemplatesTest.php:102](/home/wilt/Projects/zencart/not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php:102)

The test asserts:

```php
$template_dir === 'admin_template_test'
```

and:

```php
DIR_WS_TEMPLATE === 'includes/templates/admin_template_test/'
```

These are the two strongest signals that the admin init file used the active template from the DB instead of falling back to `template_default`.

### What a passing result means

A pass means:

- an empty incoming `$template_dir` is corrected by reading the active template
- admin bootstrap resolves the selected template as intended

### What the current failure means

On the current branch, the test fails because `$template_dir` becomes `template_default` instead of `admin_template_test`.

That supports the review comment that the conditional guard is reversed.

## Running the Tests

Targeted commands:

```bash
vendor/bin/phpunit --filter TemplateSelectTest not_for_release/testFramework/FeatureAdmin/TemplateTests/TemplateSelectTest.php
vendor/bin/phpunit --filter BaseLanguageLoaderTemplateChainTest not_for_release/testFramework/Unit/testsTemplateResolver/BaseLanguageLoaderTemplateChainTest.php
vendor/bin/phpunit --filter AdminInitTemplatesTest not_for_release/testFramework/Unit/testsTemplateResolver/AdminInitTemplatesTest.php
```

## Reading Failures

When these tests fail, the failure output is intended to be directly interpretable:

- `TemplateSelectTest`:
  - compare expected template dropdown default vs actual default
- `BaseLanguageLoaderTemplateChainTest`:
  - compare expected list of inherited language files vs actual files returned
- `AdminInitTemplatesTest`:
  - compare expected active template key vs actual template key used during bootstrap

## Scope Note

These tests are not a complete verification suite for the encapsulated template plugin feature.

They exist to pin down a small set of review findings:

- wrong template preselected in admin edit form
- incomplete parent/child plugin-template language inheritance
- reversed empty/non-empty guard during admin template bootstrap

If the production design changes, the tests and this document should be updated together so the intended contract remains explicit.
