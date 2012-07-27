// Written by Phil Dougherty- 6/27/2012

    testHelper - Puts all tests into memory with a simple API to run individual or all tests

    test - Some core functions for individual tests to implement

    testResult - organized way for tests to communicate status to testHelper. Consists of three variables- returnCode, description, and data.
        'returnCode' is
            tr_SUCCESS (0) - success
            tr_FAIL    (1) - fail
            tr_NA      (2) - N/A
        'description' and 'data' are optional.

    model - A way for tests to communicate data (for example, the gameId created by 'createGame' will be needed for most other functions)

    unitTests folder - Consists of a list of php files, each following strict design constraints:
        1. The name of the file is synonymous with the name of the test in testHelper's output (This needn't be the name of a particular function to test).
        2. Each test must extend 'Test' (require "test.php", located in the main test folder).
        3. Two functions are expected for compatibility with testHelper. 'solo()' for running on an individual basis, and 'group()' for running as a string of tests. Failure to implement either of these will result in a return of 'tr_NA' as a result
        4. If a previous test is required for this test to successfully run, give the test a variable '$dependancy' and set it to the string name of the test it depends on (ex: $dependancy = "createGame"). (Note*- the dependancy tree is traversed in a breadth-first manner, meaning that if a test has multiple dependancies, one only needs to set its dependancy to any test at a sufficient depth)
    


    LIST OF EXPECTED TESTS

    complete    name                            dependancy              description
        x    createPlayer                           x
        x    createEditor                           x
        x    createGame                         createEditor
        x    createItem                         createGame
        x    createNpc                          createGame
        x    createPlaque                       createGame
        x    createWebpage                      createGame
        x    createAugbubble                    createGame
        -    createNote                         createGame
        x    createItemLocations                createItem
        x    createNpcLocations                 createNpc
        x    createPlaqueLocations              createPlaque
        x    createWebpageLocations             createWebpage
        x    createAugbubbleLocations           createAugbubble
        -    createNoteLocations                createNote
        x    getLocationsBeforeReqs             createItemLocations     Should return all locations
        x    setRequirementsForLocs             getLocationsBeforeReqs
        x    getLocationsAfterReqs              setRequirementsForLocs  Should return NO locations
        -    passRequirements                   getLocationsAfterReqs   
        -    getLocationsAfterCompleteReqs      passRequirements        Should return all locations


Check ./unitTests/exampleTest.php for an idea of what a test *should* look like.
