# Back Office Theme

This is thirty bees' default and only back office theme. It comes with a couple of styles.


## Building

Only styles need building. If tools were installed as described in [Prerequisites](#Prerequisites), it goes like this:
```
  cd admin-dev/themes/default
  "${HOME}"/.gem/bin/compass compile
```
Compiled results get committed into the code repository.


## Prerequisites

Compiling the theme requires Compass, a tool written in Ruby.

### Installation on Debian/Ubuntu:

This is a conservative approach, keeping system wide installations at a minimum.
```
  sudo snap install ruby
  gem install compass
```

### Cleanup on Debian/Ubuntu:

Tidy people may want to cleanup after being done. This assumes no other Ruby Gems are installed:
```
  sudo snap remove ruby
  rm -r "${HOME}"/.gem
```
