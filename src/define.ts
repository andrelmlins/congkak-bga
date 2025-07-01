define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui', 'ebg/counter', 'ebg/zone', 'ebg/stock'], function (
  dojo,
  declare
) {
  return declare('bgagame.congkak', ebg.core.gamegui, new Congkak());
});
