@suggest
Feature: applying restriction to entity clone module.

  Ensure that site selector widget meets the expected requirements.

  @api @suggest
  Scenario: editors can access entity clone that site-admin user created.
    Given sites terms:
      | name                 | parent          | tid   | uuid                                  |
      | Test Site 1          | 0               | 10010 | 11dede11-10c0-111e1-1100-000000000031 |
      | Test Section 11      | Test Site 1     | 10011 | 11dede11-10d0-111e1-1100-000000000032 |
      | Test Section 12      | Test Site 1     | 10014 | 11dede11-10g0-111e1-1100-000000000035 |
      | Test Site 2          | 0               | 10015 | 11dede11-10h0-111e1-1100-000000000036 |
      | Test Site 3          | 0               | 10016 | 11dede11-10i0-111e1-1100-000000000037 |

    And topic terms:
      | name         | parent | tid   |
      | Test topic 1 | 0      | 10017 |
      | Test topic 2 | 0      | 10018 |
      | Test topic 3 | 0      | 10019 |

    And users:
      | name        | status | uid    | mail                    | pass         | field_user_site | roles  |
      | test.editor |      1 | 999999 | test.editor@example.com | L9dx9IJz3'M* | Test Section 11 | Editor |

    And test content:
      | title       | path       | moderation_state | uuid                                | field_node_site                                             | field_node_primary_site | nid     | field_topic  |
      | [TEST] LP 1 | /test-lp-1 | published        | 99999999-aaaa-bbbb-ccc-000000000001 | Test Site 1, Test Section 11                                | Test Site 1             | 999999  | Test topic 1 |
      | [TEST] LP 2 | /test-lp-2 | published        | 99999999-aaaa-bbbb-ccc-000000000002 | Test Site 1, Test Section 11, Test Section 12               | Test Site 1             | 999998  | Test topic 2 |
      | [TEST] LP 3 | /test-lp-3 | published        | 99999999-aaaa-bbbb-ccc-000000000003 | Test Site 2                                                 | Test Site 2             | 999997  | Test topic 3 |
    When I am logged in as "test.editor"
    Then I go to "/node/999999/edit"
    Then I should get a 200 HTTP response
    Then save screenshot
    Then I should see an "input#edit-field-node-site-10010" element
    And I should see an "input#edit-field-node-site-10011" element
    And I should see an "input#edit-field-node-site-10014" element
    And I fill in "Title" with "Bibendum Pharetra Inceptos"
    And I fill in "Summary" with "Cras Tristique Risus"
    And I fill in "Topic" with "Test topic 1 (10017)"
    And I select "Draft" from "Change to"
    When I go to "/entity_clone/node/999999"
    Then I should get a 200 HTTP response
    When I go to "/entity_clone/node/999998"
    Then I should get a 200 HTTP response
    When I go to "/entity_clone/node/999997"
    Then I should get a 404 HTTP response

