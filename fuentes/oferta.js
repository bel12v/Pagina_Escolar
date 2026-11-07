// Fuente B (MongoDB): oferta de materias por periodo.
// Cargar con:  mongosh mongodb://127.0.0.1:27017 fuentes/oferta.js
// La materia se identifica por "clave_materia" (en MySQL se llama "clave").

db = db.getSiblingDB("servicios_escolares");
db.oferta_materias.drop();   // se puede volver a ejecutar

db.oferta_materias.insertMany([
  { clave_materia: "CAL601", periodo: "2026-B", cupo: 30, inscritos: 25 },
  { clave_materia: "FIS603", periodo: "2026-B", cupo: 30, inscritos: 30 },
  { clave_materia: "LIT604", periodo: "2026-B", cupo: 25, inscritos: 0 },   // 0 real
  { clave_materia: "ING606", periodo: "2026-B", cupo: 35, inscritos: 12 },
  { clave_materia: "QUI999", periodo: "2026-B", cupo: 20, inscritos: 8 },   // no existe en MySQL
]);
// HIS605 y ETI607 existen en MySQL pero no tienen oferta.

print("oferta_materias: " + db.oferta_materias.countDocuments() + " documentos");
