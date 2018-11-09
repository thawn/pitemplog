module.exports = {
  js: { 
    files: {
      'build/_includes/js.html': ['_src/_includes/js.html']
    }
  },
  conf: { 
    files: {
      'build/conf/conf.php': ['_src/conf/conf.php']
    }
  },
  min: {
    files: {
      'build/_includes/head.html': ['_src/_includes/head.html']
    }
  }
};