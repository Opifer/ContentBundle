angular.module('OpiferContent', ['angular-inview'])

    .factory('ContentService', ['$resource', '$routeParams', function ($resource, $routeParams) {
        return $resource(Routing.generate('opifer_content_api_content') + '/:id', {}, {
            index: {method: 'GET', params: {}, cache: true},
            delete: {method: 'DELETE', params: {id: $routeParams.id}}
        });
    }])

    .factory('DirectoryService', ['$resource', '$routeParams', function ($resource, $routeParams) {
        return $resource(Routing.generate('opifer_content_api_directory'), {}, {
            index: {method: 'GET', params: {}, cache: true}
        });
    }])

    .controller('ContentPickerController', ['$scope', '$http', '$rootScope', function ($scope, $http, $rootScope) {
        $scope.content = {};
        $scope.selecteditems = [];
        $scope.formname = '';
        $scope.multiple = false;
        $scope.order = {
            sort: 'manual',
            order: []
        };

        $scope.sortableOptions = {
            // Update the order variable when the order of items has changed
            stop: function () {
                var order = [];
                for (var i = 0; i < $scope.selecteditems.length; i++) {
                    order.push($scope.selecteditems[i].id);
                }

                $scope.order.order = order;
            }
        };

        /**
         * Set content
         *
         * @param  {array} content
         * @param  {string} formname
         * @param {bool} multiple
         * @param {string} directoryId
         */
        $scope.init = function (content, formname, multiple) {
            $scope.formname = formname;

            if (angular.isDefined(multiple) && multiple) {
                $scope.multiple = multiple;
            }

            if ($scope.multiple) {
                // When items have been passed to the init function, retrieve the related data.
                if (angular.isDefined(content)) {
                    content = JSON.parse(content);
                    if (content.length) {
                        $scope.order.order = content;
                        content = content.toString();

                        $http.get(Routing.generate('opifer_content_api_content_ids', {'ids': content}))
                            .success(function (data) {
                                var results = data.results;
                                for (var i = 0; i < results.length; i++) {
                                    $scope.selecteditems.push(results[i]);
                                }
                            })
                        ;
                    }
                }
            } else {
                $scope.content = JSON.parse(content);
            }
        };

        $scope.hasContent = function(content) {
            for (var i = 0; i < $scope.selecteditems.length; i++) {
                if (content.id == $scope.selecteditems[i].id) {
                    return true;
                }
            }
            return false;
        };

        // Select a content item
        $scope.pickContent = function(content) {
            $rootScope.$emit('contentPicker.pickContent', content);

            if ($scope.multiple) {
                $scope.selecteditems.push(content);
                $scope.order.order.push(content.id);
            } else {
                $scope.content = content;
                $scope.isPickerOpen = false;
            }
        };

        // Select a content item
        $scope.unpickContent = function(content) {
            for (var i = 0; i < $scope.selecteditems.length; i++) {
                if (content.id == $scope.selecteditems[i].id) {
                    $scope.selecteditems.splice(i, 1);
                }
            }
        };

        // Remove a content item
        $scope.removeContent = function (idx) {
            $scope.selecteditems.splice(idx, 1);
        };
    }])

/**
 * Content browser directive
 */
    .directive('contentBrowser', function () {

        return {
            restrict: 'E',
            scope: {
                name: '@',
                value: '@',
                formid: '@',
                provider: '@',
                context: '@',
                siteId: '@',
                active: '=',
                directoryId: '@',
                //locale: '@',
                mode: '@',
                receiver: '@'
            },
            templateUrl: '/bundles/opifercontent/app/content/content.html',
            controller: function ($scope, ContentService, DirectoryService, $attrs) {
                var pageLoaded = false;
                $scope.navto = false;
                $scope.maxPerPage = 100;
                $scope.currentPage = 1;
                $scope.numberOfResults = 0;
                $scope.remainingResults = 0;
                $scope.lastBrowsedResults = [];
                $scope.contents = [];
                $scope.lblPaginate = "Meer resultaten";
                $scope.query = null;
                $scope.inSearch = false;
                $scope.busyLoading = false;
                $scope.confirmation = {
                    shown: false,
                    name: '',
                    action: ''
                };

                if (typeof $scope.history === "undefined") {
                    $scope.history = [];
                    $scope.histPointer = 0;
                    $scope.history.push(0);
                }

                $scope.$watch('directoryId', function (newValue, oldValue) {
                    if ($scope.navto === true) {
                        if ($scope.history.length) {
                            $scope.history.splice($scope.histPointer + 1, 99);
                            $scope.histPointer++;
                            $scope.history.push(newValue);
                        } else {
                            $scope.history.push(newValue);
                            $scope.histPointer = 1;
                        }
                        $scope.navto = false;
                    }
                });


                $scope.fetchContents = function () {
                    if ($scope.active == false) {
                        return;
                    }
                    ContentService.index({
                            site_id: $scope.siteId,
                            directory_id: $scope.directoryId,
                            //locale: $scope.locale,
                            q: $scope.query,
                            p: $scope.currentPage,
                            limit: $scope.maxPerPage
                        },
                        function (response, headers) {
                            for (var key in response.results) {
                                $scope.contents.push(response.results[key]);
                            }
                            $scope.numberOfResults = response.total_results;
                            $scope.remainingResults = $scope.numberOfResults - ($scope.currentPage * $scope.maxPerPage);
                            $scope.lblPaginate = "Meer content (" + $scope.remainingResults + ")";
                            $scope.busyLoading = false;
                        });
                };

                $scope.searchContents = function () {
                    ContentService.index({
                            site_id: $scope.siteId,
                            directory_id: 0,
                            //locale: $scope.locale,
                            q: $scope.query,
                            p: $scope.currentPage,
                            limit: $scope.maxPerPage
                        },
                        function (response, headers) {
                            $scope.directorys = [];
                            $scope.contents = [];
                            for (var key in response.results) {
                                $scope.contents.push(response.results[key]);
                            }
                            $scope.numberOfResults = response.total_results;
                            $scope.btnPaginate.button('reset');
                            $scope.lblPaginate = "Meer content (" + ($scope.numberOfResults - ($scope.currentPage * $scope.maxPerPage)) + ")";
                        });
                };

                $scope.$watchCollection('[query]', _.debounce(function () {
                    if ($scope.query) {
                        $scope.currentPage = 1;
                        $scope.inSearch = true;
                        $scope.searchContents();
                    } else if ($scope.inSearch) {
                        $scope.clearSearch();
                    }
                }, 300));
                $scope.fetchContents();

                $scope.fetchDirectorys = function () {
                    DirectoryService.index({
                            site_id: $scope.siteId,
                            directory_id: $scope.directoryId,
                            //locale: $scope.locale
                        },
                        function (response) {
                            $scope.directorys = response.directories;
                            if (!pageLoaded) {
                                var parents = response.parents;
                                angular.forEach(parents, function (value, key) {
                                    $scope.histPointer++;
                                    $scope.history.push(value);
                                });
                                pageLoaded = true;
                            }
                        });
                };
                $scope.fetchDirectorys();

                $scope.reloadContents = function () {
                    $scope.contents = [];
                    $scope.currentPage = 1;
                    $scope.fetchContents();
                    $scope.fetchDirectorys();
                };

                $scope.clearSearch = function () {
                    $scope.query = null;
                    $scope.inSearch = false;
                    $scope.currentPage = 1;
                    $scope.contents = [];
                    $scope.fetchContents();
                    $scope.fetchDirectorys();
                };

                $scope.deleteContent = function (id) {
                    angular.forEach($scope.contents, function (c, index) {
                        if (c.id === id) {
                            ContentService.delete({id: c.id}, function () {
                                $scope.contents.splice(index, 1);
                            });
                        }
                    });

                    $scope.confirmation.shown = false;
                };

                $scope.confirmDeleteContent = function (idx, $event) {
                    var selected = $scope.contents[idx];

                    $scope.confirmation.idx = selected.id;
                    $scope.confirmation.name = selected.title;
                    $scope.confirmation.dataset = $event.currentTarget.dataset;
                    $scope.confirmation.action = $scope.deleteContent;
                    $scope.confirmation.shown = !$scope.confirmation.shown;
                };

                $scope.previous = function () {
                    if ($scope.history.length) {
                        $scope.directoryId = $scope.history[$scope.histPointer - 1];
                        $scope.histPointer--;
                        $scope.reloadContents();
                    }
                };

                $scope.next = function () {
                    if ($scope.history.length) {
                        $scope.directoryId = $scope.history[$scope.histPointer + 1];
                        $scope.histPointer++;
                        $scope.reloadContents();
                    }
                };

                $scope.navigateToDirectory = function (directory) {
                    $scope.navto = true;
                    $scope.directoryId = directory.id;
                    $scope.numberOfResults = $scope.maxPerPage - 1; // prevent infinitescrolling
                    $scope.reloadContents();
                };

                $scope.editContent = function (id) {
                    window.location = Routing.generate('opifer_content_content_edit', {'id': id, 'directoryId': $scope.directoryId});
                };

                $scope.editUrl = function (id) {
                    return Routing.generate('opifer_content_content_edit', {'id': id, 'directoryId': $scope.directoryId});
                };

                $scope.copyContent = function (id) {
                    window.location = Routing.generate('opifer_content_content_duplicate', {'id': id});
                };

                $scope.confirmCopyContent = function (idx, $event) {
                    var selected = $scope.contents[idx];

                    $scope.confirmation.idx = selected.id;
                    $scope.confirmation.name = selected.title;
                    $scope.confirmation.dataset = $event.currentTarget.dataset;
                    $scope.confirmation.action = $scope.copyContent;
                    $scope.confirmation.shown = !$scope.confirmation.shown;
                };

                $scope.loadMore = function (e) {
                    if (!$scope.active || $scope.busyLoading || $scope.remainingResults <= 0) return;
                    $scope.currentPage++;
                    $scope.busyLoading = true;
                    $scope.fetchContents();
                };

                $scope.pickObject = function (contentId) {
                    $scope.$parent.pickObject(contentId);
                };

                $scope.unpickObject = function (contentId) {
                    $scope.$parent.unpickObject(contentId);
                };

                $scope.pickContent = function (content) {
                    $scope.$parent.pickContent(content);
                };

                $scope.unpickContent = function (content) {
                    $scope.$parent.unpickContent(content);
                };

                $scope.hasContent = function (content) {
                    return $scope.$parent.hasContent(content);
                };

                $scope.$on('ngModal.close', function() {
                    $scope.active = false;
                });
            },
            compile: function (element, attrs) {
                if (!attrs.mode) {
                    attrs.mode = 'ADMIN';
                }
            }
        };
    })
;