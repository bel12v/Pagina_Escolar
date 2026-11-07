PRÁCTICA: SISTEMAS DE MULTIBASE DE DATOS


1. DÓNDE ESTÁ CADA FUENTE (punto 7)

Fuente A: MySQL 8.
Está en localhost, puerto 3306.
La base se llama pagina_escolar. Es una copia de pruebas de cobach_bc.
Usamos la tabla materias, que tiene 6 materias.

Fuente B: MongoDB.
Está en localhost, puerto 27017.
La base se llama servicios_escolares.
Usamos la colección oferta_materias, que tiene 5 documentos.
Los datos se cargan con este comando:
mongosh mongodb://127.0.0.1:27017 fuentes/oferta.js

Cada fuente funciona por separado. Cada una tiene su propio gestor y su propio puerto.


2. INTEGRACIÓN Y CORRESPONDENCIA (punto 8)

La capa de integración es el archivo api/materias_global.php.
Para MySQL usa el modelo que ya existía, models/materia.php.
Para MongoDB usa models/OfertaMongo.php.

La materia es el mismo concepto en las dos fuentes, pero el campo se llama distinto:
clave (en MySQL) = clave_materia (en MongoDB)

QUI999 solo existe en MongoDB.
HIS605 y ETI607 solo existen en MySQL.
Esos registros salen como sin_correspondencia.


3. COMANDOS DE LA DEMO

Punto 9: consulta global

Ver las materias de MySQL:
curl -s http://localhost:8000/api/materias.php

Ver la oferta de MongoDB:
mongosh --quiet mongodb://127.0.0.1:27017/servicios_escolares --eval 'db.oferta_materias.find()'

Ver el resultado integrado:
curl -s http://localhost:8000/api/materias_global.php

Punto 10: actualización

Cambiar el nombre de la materia 1. Usa la transacción de models/materia.php:
curl -s -X PUT "http://localhost:8000/api/materias.php?id=1" -d '{"nombre":"Cálculo Diferencial e Integral","clave":"CAL601","creditos":5}'

Revisar que el cambio aparece en la consulta global:
curl -s http://localhost:8000/api/materias_global.php

Punto 11: autonomía y fallos

Apagar MongoDB:
brew services stop mongodb-community

MySQL sigue funcionando:
curl -s http://localhost:8000/api/materias.php

La consulta global sale con estado parcial y los campos de MongoDB en null:
curl -s http://localhost:8000/api/materias_global.php

Volver a encender MongoDB:
brew services start mongodb-community


4. QUÉ PUEDE HACER CADA FUENTE

MySQL (tabla materias):
- Lectura: con GET /api/materias.php.
- Escritura: con POST, PUT y DELETE en /api/materias.php.
- Transacciones locales: sí, es ACID. La función actualizar() usa beginTransaction, commit y rollBack.
- Limitación: no sabe nada de la oferta (periodo, cupo, inscritos).

MongoDB (colección oferta_materias):
- Lectura: con la función obtenerTodas() de OfertaMongo.
- Escritura: solo con mongosh, no hay endpoint.
- Transacciones locales: cada documento se guarda de forma atómica.
- Limitación: no sabe el nombre ni los créditos de la materia. Como es un MongoDB standalone, no tiene transacciones de varios documentos.


5. SI UNA OPERACIÓN MODIFICARA AMBAS FUENTES

No hay atomicidad global.
MySQL y MongoDB no comparten un coordinador de transacciones, así que no hay 2PC.
Cada fuente haría su propia transacción local.
Si una confirma y la otra falla, el cambio queda incompleto.
Para manejarlo, se registraría la operación, por ejemplo en una tabla de solicitudes con su estado.
Después se reintentaría la parte que falló hasta completarla.
Otra opción es compensar, es decir, deshacer la parte que sí se hizo.
