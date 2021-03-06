## Integrate with l5-swagger
There is a laravel package called darkaonline/l5-swagger that is created to display a swagger ui page that shows the REST API in a browser-friendly interface. With a little bit of tweaking it is possible to use this package to show the OpenAPI spec created by this tool.

- Follow the steps at https://github.com/DarkaOnLine/L5-Swagger to install the library.
- Publish the config and change the url in the config to a different url to avoid a route conflict.
- Go to the page generated by the library and in the input field at the top replace the path with /api/doc.json and click 'explore'

It is possible to modify the template to always show the documentation.
- Run artisan publish to publish the views of l5-swagger.
- Open file resources/views/vendor/l5-swagger/index.blade.php
- Replace this part of code the url definition:
```javascript
const ui = SwaggerUIBundle({
    dom_id: '#swagger-ui',

    url: "{!! route('apie.docs') !!}",
```
    
Now if you refresh you will see your REST API right away.
![screenshot](l5swagger-screenshot.png?raw=true)
