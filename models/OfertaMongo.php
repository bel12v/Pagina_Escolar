<?php
// models/OfertaMongo.php
// Fuente B: lee la colección oferta_materias de MongoDB (base servicios_escolares).
// Usa directamente la extensión mongodb de PHP (MongoDB\Driver\Manager), sin Composer.

class OfertaMongo {
    private $manager;

    public function __construct($uri = 'mongodb://127.0.0.1:27017') {
        $this->manager = new MongoDB\Driver\Manager(
            $uri,
            // Timeout de 2 s: si MongoDB no responde, falla rápido.
            ['connectTimeoutMS' => 2000, 'serverSelectionTimeoutMS' => 2000],
            // Conexión nueva en cada petición, para notar al instante cuando MongoDB vuelve.
            ['disableClientPersistence' => true]
        );
    }

    // Devuelve la oferta indexada por clave_materia.
    // Si MongoDB no responde, lanza una excepción (la maneja materias_global.php).
    public function obtenerTodas() {
        $query = new MongoDB\Driver\Query([], ['projection' => ['_id' => 0]]);
        $cursor = $this->manager->executeQuery('servicios_escolares.oferta_materias', $query);

        $oferta = [];
        foreach ($cursor as $doc) {
            $oferta[$doc->clave_materia] = (array) $doc;
        }
        return $oferta;
    }
}
?>
