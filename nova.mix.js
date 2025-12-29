const mix = require('laravel-mix');
const path = require('path');

class NovaExtension {
  // eslint-disable-next-line class-methods-use-this
  name() {
    return 'nova-extension';
  }

  register(name) {
    this.name = name;
  }

  webpackConfig(webpackConfig) {
    webpackConfig.externals = {
      vue: 'Vue',
      'laravel-nova': 'LaravelNova',
      'laravel-nova-ui': 'LaravelNovaUi',
    };

    webpackConfig.output = {
      uniqueName: this.name,
    };
  }
}

mix.extend('nova', new NovaExtension());
