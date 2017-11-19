var geoip = require('geoip-lite');
 
var ip = '138.197.206.13';
var geo = geoip.lookup(ip);
 
console.log(geo);
