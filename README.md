# Plugin Mercado Libre 
Plugin para sincronizar Mercado Libre con Shopaholic 

### Descripción
Al publicar el plugin, el archivo composer.json debe tener este contenido JSON como mínimo. Tenga en cuenta que el nombre del paquete debe terminar con **-plugin** e incluir el paquete **composer/installers** como una dependencia.

```
{
    "name": "sitios/base-plugin",
    "type": "october-plugin",
    "description": "Descripcion del plugin aquí",
    "require": {
        "composer/installers": "~1.0"
    }
}
```
### Requiriendo una versión de OctoberCMS

Simplemente haga `require` del paquete **october/rain** a la versión deseada. Lo siguiente requerirá que la instalación de la plataforma use la versión **x.y** de October CMS o superior.

```
"require": {
    "october/rain": ">=2.1"
}
```

### Requerir otro complemento

Navegue hasta el directorio plugin y abra el archivo `composer.json` para incluir una dependencia y su versión de destino. Lo siguiente incluirá el complemento Acme.Blog con un rango de versión de 1.2.

```
"require": {
    "acme/blog-plugin": "^1.2"
}
```

### Desarrollo con paquetes de terceros

Para crear un nuevo plugin que use un paquete o librería externa, debe instalarlo en su archivo raíz de `composer` y luego copiar la definición en su archivo de `composer` del plugin. Por ejemplo, si desea que su plugin `acme/blog-plugin` dependa del paquete `aws/aws-sdk-php`.

1 - En el directorio raíz, ejecute `composer require aws/aws-sdk-php`. Esto instalará el paquete en el ``composer`` del proyecto y garantizará que sea compatible con otros paquetes.

2 - Una vez completado, abra el archivo ``composer.json`` del directorio raíz para ubicar la dependencia recién definida. Por ejemplo, verás algo como esto:

```
"require": {
    "aws/aws-sdk-php": "^3.158"
}
```

3 - Copie esta definición del archivo raíz ``composer.json`` e inclúyala en el archivo ``plugins/acme/blog/composer.json`` para su plugin. Ahora la dependencia está disponible para su aplicación y también es requerida por el plugin para su uso.

### Etiquetado de un deploy de plugins

Los paquetes en **OctoberCMS** siguen el control de versiones semántico y **Composer** usa **git** para determinar la estabilidad y el impacto de una versión determinada.

### Listado de sus etiquetas

Use el comando ``git tag`` para listar las etiquetas existentes para su paquete.

```
$ git tag
v1.0
v2.0
```

### Creando una nueva etiqueta

Para crear una nueva etiqueta, agregue *(-a)* la versión con un mensaje opcional *(-m)*.

```
git tag -a v2.0.1 -m "Versión 2 esta lista!"
```

### Complementos privados

**Composer** le permite agregar repositorios privados de GitHub y otros proveedores a sus proyectos de **OctoberCMS**. Asegúrese de haber seguido las mismas instrucciones para publicar complementos y temas respectivamente.

En todos los casos, debe tener una copia de su plugin almacenado en algún lugar disponible para el proyecto principal. Los comandos **plugin:install** se pueden usar para instalar *plugins* privados desde una fuente remota o local. Esto agregará la ubicación a su archivo de composición y lo instalará como cualquier otro paquete.

### Instalar desde una fuente remota

Utilice la opción ``--from`` para especificar la ubicación de su fuente remota durante la instalación.

```
php artisan plugin:install Sitios.Base --from=https://github.com/Sitios-Agencia-Digital/sitios-base-plugin.git
```

To use a specific version or branch, use the ``--want`` option, for example to request the develop branch version.

```
php artisan plugin:install Acme.Blog --from=Sitios.Base --from=https://github.com/Sitios-Agencia-Digital/sitios-base-plugin.git --want=dev-develop
```

### Instalar desde una fuente local
Para instalar un plugin usando **composer** desde la misma fuente del proyecto.

```
php artisan plugin:install Acme.Blog --from=./plugins/acme/blog
```

También puede usar una fuente que se encuentre en una unidad local o de red.

```
php artisan plugin:install Acme.Blog --from=/home/sam/private-plugins/acme-blog
```

