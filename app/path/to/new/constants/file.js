angular.module('config', [])

.constant('package', {name:'trick-or-eat',private:true,version:'0.0.0',description:'The front end for the trick-or-eat application',repository:'https://bitbucket.com/trickoreat/app',license:'MIT',dependencies:{angular:'^1.7.8','angular-animate':'^1.7.8','angular-aria':'^1.5.8','angular-cookies':'^1.5.8','angular-loader':'^1.5.8','angular-material':'^1.1.0','angular-material-data-table':'^0.10.10','angular-message-format':'^1.5.8','angular-messages':'^1.5.8','angular-mocks':'^1.5.8','angular-parse-ext':'^1.5.8','angular-resource':'^1.5.8','angular-route':'^1.5.8','angular-sanitize':'^1.5.8','angular-scenario':'^1.5.8','angular-touch':'^1.5.8','material-design-icons':'3.0.1','ng-file-upload':'^12.2.13'},devDependencies:{grunt:'^1.0.3','grunt-contrib-concat':'^1.0.1','grunt-contrib-uglify':'^3.3.0','grunt-contrib-watch':'^1.1.0','grunt-ng-constant':'^2.0.1'}})

.constant('ENV', {name:'prod'})

.value('debug', true)

;