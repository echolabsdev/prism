# Prism Docs

The Prism docs are built using [Vitepress](https://vitepress.dev) and deployed via [Netlify](https://www.netlify.com).

> [!IMPORTANT]
> Make sure you review the [documentation style guide](./documentation-style-guide.md)!

## Development

```shell
npm run docs:dev
```

### Adding a new Provider

- Update `getting-started/introduction.md` and add the new provider to the supported provider list
  - Ensure the provider list remains in alphanumeric order
- Create a provider page in `providers/{provider.md}` using the template (`providers/provider.md.template`)
  - Remove any sections that are not needed
- Update `./vitepress/config.mts` and add the provider to the provider sidebar
  - Ensure the provider sidebar section remains in alphanumeric order

## Production

```shell
npm run docs:build
```
