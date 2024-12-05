@suggest
Feature: applying restriction to entity clone module.

  Ensure that site selector widget meets the expected requirements.

  @api @suggest
  Scenario: editors can access entity clone that site-admin user created.
    Given sites terms:
      | name                 | parent          |
      | Test Site 1          | 0               |
      | Test Section 11      | Test Site 1     |
      | Test Section 12      | Test Site 1     |
      | Test Site 2          | 0               |
      | Test Site 3          | 0               |

    And topic terms:
      | name         | parent |
      | Test topic 1 | 0      |
      | Test topic 2 | 0      |
      | Test topic 3 | 0      |

    And users:
      | name        | status | uid    | mail                    | pass         | field_user_site | roles  |
      | test.editor |      1 | 999999 | test.editor@example.com | L9dx9IJz3'M* | Test Section 11 | Editor |
      | test.admin  |      1 | 999995 | site.admin@example.com  | L9dx9IJz2'M* | Test Section 11 | Site Admin |

    And test content:
      | title       | path       | moderation_state | body  |field_node_site                                             | field_node_primary_site | field_topic  | author      |
      | [TEST] LP 1 | /test-lp-1 | published        | test  |Test Site 1, Test Section 11                                | Test Site 1             | Test topic 1 | test.editor |
      | [TEST] LP 2 | /test-lp-2 | published        | test  |Test Site 1, Test Section 11, Test Section 12               | Test Site 1             | Test topic 2 | test.editor |
      | [TEST] LP 3 | /test-lp-3 | published        | test  |Test Site 2                                                 | Test Site 2             | Test topic 3 | test.admin  |
    Given I am logged in as "test.editor"
    When I visit test "[TEST] LP 1"
    Then I should get a 200 HTTP response
    Then I should see the link "Clone"
    And I click "Clone"
    Then I should get a 200 HTTP response
    When I visit test "[TEST] LP 2"
    Then I should get a 200 HTTP response
    Then I should see the link "Clone"
    And I click "Clone"
    Then I should get a 200 HTTP response
    When I visit test "[TEST] LP 3"
    Then I should get a 200 HTTP response
    Then I should not see the link "Clone"
    Given I am logged in as "test.admin"
    When I visit test "[TEST] LP 1"
    Then I should get a 200 HTTP response
    Then I should see the link "Clone"
    And I click "Clone"
    Then I should get a 200 HTTP response
    When I visit test "[TEST] LP 2"
    Then I should get a 200 HTTP response
    Then I should see the link "Clone"
    And I click "Clone"
    Then I should get a 200 HTTP response
    When I visit test "[TEST] LP 3"
    Then I should get a 200 HTTP response
    Then I should see the link "Clone"
    And I click "Clone"
    Then I should get a 200 HTTP response
