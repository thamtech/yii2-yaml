Change Log
==========

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/)
and this project adheres to [Semantic Versioning](https://semver.org).


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
