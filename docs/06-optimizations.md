## Optimizations for production
# Run config:cache
First of all make sure you use config:cache so the configuration is cached, especially if you use the [auto-discover](../README.md#automate-registering-api-resources)
functionality of the api resource classes.

# dump OpenAPI Spec
The OpenAPI spec is normally built on every request. This is too slow for production so it's best to dump the file as a static file
in your public(_html) folder.

All you have to do is run the console command to dump it.
First look up the URI:
```bash
./artisan route:list --name=apie.docs -c
```

And then dump it in your public or public_html folder (if the uri is api/doc.json and it is stored in a public_html folder):
```bash
./artisan apie:dump-open-api public_html/api/doc.json
```
