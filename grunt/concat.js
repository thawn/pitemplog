module.exports = {
  dist: {
    src: [
      'node_modules/jquery/dist/jquery.js',
      'node_modules/bootstrap/dist/js/bootstrap.js',
      'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.js',
      'node_modules/amcharts3/amcharts/amcharts.js',
      'node_modules/amcharts3/amcharts/serial.js',
      'node_modules/amcharts3/amcharts/themes/dark.js',
      'node_modules/amcharts3/amcharts/plugins/export/export.js',
      'node_modules/amcharts3/amcharts/plugins/export/libs/fabric.js/fabric.js',
      'node_modules/amcharts3/amcharts/plugins/export/libs/FileSaver.js/FileSaver.js',
//      'node_modules/amcharts3/amcharts/plugins/export/libs/jszip/jszip.js',
//      'node_modules/amcharts3/amcharts/plugins/export/libs/pdfmake/pdfmake.js',
//      'node_modules/amcharts3/amcharts/plugins/export/libs/pdfmake/vfs_fonts.js',
      'assets/js/custom.js'
    ],
    dest: 'build/assets/js/jquery_bootstrap_amcharts_custom.js'
  },
  css: {
    src: [
      'node_modules/bootstrap/dist/css/bootstrap.css',
      'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker3.standalone.css',
      'node_modules/amcharts3/amcharts/plugins/export/export.css',
      'node_modules/@fortawesome/fontawesome-free/css/fontawesome.css',
      'node_modules/@fortawesome/fontawesome-free/css/solid.css',
      'assets/css/custom.css'
    ],
    dest: 'build/assets/css/bootstrap_datepicker_custom.css'
  }
};