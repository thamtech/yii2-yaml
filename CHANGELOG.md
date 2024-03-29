Change Log
==========

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/)
and this project adheres to [Semantic Versioning](https://semver.org).


[v1.0.0]
--------

### Changed
- Bump phpunit version
- Require PHP >=7.2.5
- Expand supported versions of upstream `symfony/yaml` library
- `UnsetArrayValue` tags now encode with a `null` value instead of `{  }`. This
  is due to a change in the upstream `symfony/yaml` library.


[v0.3.3]
--------

### Changed
- Add `asset-packagist.org` repository
- Bump phpunit version

### Fixed
- Restrict upstream `symfony/yaml` to `<=3.4.29` to maintain support for
  custom tags with empty values such as `UnsetArrayValue`.


[v0.3.2]
--------

### Changed
- Improved support for custom handlers based on class or interface names. Values
  can now be handled by any class, superclass, or interface implemented by the
  value. Previously, on the value's `get_class()` result could be configured as
  a handler.


[v0.3.1]
--------

### Fixed
- ValueEvent should not automatically unwrap the TaggedValue value in
  `Symfony\Component\Yaml\Tag\TaggedValue-{tagName}` events.


[v0.3.0]
--------

### Added
- Raw, unprocessed parsed TaggedValues now trigger a ValueEvent named
  `Symfony\Component\Yaml\Tag\TaggedValue-{tagName}`. After this event is
  handled, the resulting value is processed and then fired in a separate
  `{tagName}` ValueEvent.

### Fixed
- Inner values of parsed TaggedValue objects should be processed


[v0.2.0]
--------

### Added
- `$event->handleValue($value)` shortcut for setting the `value`, and setting
  `$event->handled = true`.
- Added ability to set and override parser and dumper handlers globally
  via dependency injection mechanisms

### Changed
- *Breaking Change* - Automatically unwrap value from inside TaggedValue during
  parsing


[v0.1.0]
--------

### Added
- Initial implementation
