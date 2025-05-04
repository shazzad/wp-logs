module.exports = function (grunt) {
  "use strict";

  var pluginVersion = "";
  var pkgJson = require("./package.json");

  require("matchdep").filterDev("grunt-*").forEach(grunt.loadNpmTasks);

  grunt.getPluginVersion = function () {
    var p = "shazzad-wp-logs.php";
    if (pluginVersion == "" && grunt.file.exists(p)) {
      var source = grunt.file.read(p);
      var found = source.match(/Version:\s(.*)/);
      pluginVersion = found[1];
    }
    return pluginVersion;
  };

  grunt.initConfig({
    pkg: "<json:package.json>",
    compress: {
      main: {
        options: {
          archive: "build/shazzad-wp-logs.v" + pkgJson.version + ".zip",
        },
        files: [
          // include include admin/build folder
          { src: "admin/build/**", dest: "shazzad-wp-logs/" },
          { src: "assets/**", dest: "shazzad-wp-logs/" },
          { src: "commands/**", dest: "shazzad-wp-logs/" },
          { src: "includes/**", dest: "shazzad-wp-logs/" },
          { src: "languages/**", dest: "shazzad-wp-logs/" },
          { src: "vendor/**", dest: "shazzad-wp-logs/" },
          {
            src: "shazzad-wp-logs.php",
            dest: "shazzad-wp-logs/",
          },
          { src: "index.php", dest: "shazzad-wp-logs/" },
        ],
      },
    },
    "string-replace": {
      inline: {
        files: {
          "./": ["shazzad-wp-logs.php"],
        },
        options: {
          replacements: [
            {
              pattern: "Version: " + grunt.getPluginVersion(),
              replacement: "Version: " + pkgJson.version,
            },
            {
              pattern: "SWPL_VERSION', '" + grunt.getPluginVersion() + "'",
              replacement: "SWPL_VERSION', '" + pkgJson.version + "'",
            },
          ],
        },
      },
    },
  });

  grunt.registerTask("version", ["string-replace"]);
  grunt.registerTask("build", ["version", "compress"]);
};
