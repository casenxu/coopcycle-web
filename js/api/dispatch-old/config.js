const ConfigLoader = require('../ConfigLoader');
const Metrics = require('../Metrics')
const Sequelize = require('sequelize')

module.exports = function(rootDir) {

  var envMap = {
    production: 'prod',
    development: 'dev',
    test: 'test'
  };

  try {

    var configFile = 'config.yml';
    if (envMap[process.env.NODE_ENV]) {
      configFile = 'config_' + envMap[process.env.NODE_ENV] + '.yml';
    }

    var configLoader = new ConfigLoader(rootDir + '/app/config/' + configFile);
    var config = configLoader.load();

  } catch (e) {
    throw e;
  }

  const metrics = new Metrics({
    namespace: config.parameters.database_name,
    host: config.parameters.statsd_host,
    port: config.parameters.statsd_port,
  })

  var redis = require('redis').createClient({
    prefix: config.snc_redis.clients.default.options.prefix,
    url: config.snc_redis.clients.default.dsn
  });

  var sub = require('../RedisClient')({
    prefix: config.snc_redis.clients.default.options.prefix,
    url: config.snc_redis.clients.default.dsn
  });

  var pub = require('../RedisClient')({
    prefix: config.snc_redis.clients.default.options.prefix,
    url: config.snc_redis.clients.default.dsn
  });

  var sequelize = new Sequelize(
    config.doctrine.dbal.dbname,
    config.doctrine.dbal.user,
    config.doctrine.dbal.password,
    {
      host: config.doctrine.dbal.host,
      dialect: 'postgres',
      logging: false,
    }
  );

  return {
    metrics,
    redis,
    pub,
    sub,
    sequelize
  }
}
