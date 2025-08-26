# Backend PHP technichal test
Instrucciones 
    • Se deben tener instaladas las versiones LTS de PHP y SQLite3. En caso de que de error la extensión SQLite3, descomentarla en el archivo php.ini.
    • Tener instalado alguna herramienta para probar la API como Postman.
    • Poder ejecutar un servidor local PHP (php -S localhost:8000).
    • Posteriormente lanzar las peticiones especificadas en la documentación en Postman.

Peticiones API
Crear cliente (POST)
http://localhost/index.php/clientes
Se debe añadir al body en formato json los campos de la tabla clientes (dni, nombre, email y capital)


Mostrar datos cliente (GET)
http://localhost/index.php/clientes?dni=<DNI del cliente>
Se debe añadir en Params el dni del cliente que deseamos consultar



Modificar datos cliente (PUT)
http://localhost/index.php/clientes?dni=<DNI del cliente>
Se debe añadir en Params el dni del cliente a modificar y el resto de los campos en el body (nombre, email y capital)


Borrar cliente (DELETE)
http://localhost:8000/index.php/clientes?dni=<DNI del cliente>
Se debe añadir en Params el dni del cliente que deseamos eliminar


Simulación de hipoteca (POST)
http://localhost:8000/index.php/simulacion
Se debe añadir al body los campos necesarios (dni, tae y plazo)
