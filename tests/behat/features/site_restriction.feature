@jsonapi
Feature: site selector widget

  Ensure that site selector widget meets the expected requirements.

  @api @suggest
  Scenario: site selector widget's limitation for editors.
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

    And users:
      | name          | status | uid    | mail                      | pass         | field_user_site | roles    |
      | test.editor   | 1      | 999999 | test.editor@example.com   | L9dx9IJz3'M* | Test Section 11 | Editor   |

    And test content:
      | title       | path       | moderation_state | uuid                                | field_node_site              | field_node_primary_site | nid     | field_topic  |
      | [TEST] LP 1 | /test-lp-1 | published        | 99999999-aaaa-bbbb-ccc-000000000000 | Test Site 1, Test Section 11 | Test Site 1             | 999999  | Test topic 1 |

    When I am logged in as "test.editor"
    And I go to "node/add/test"
    Then save screenshot
    Then I should see an "input#edit-field-node-site-10010" element
    And I should see an "input#edit-field-node-site-10011" element
    And I should see an "input#edit-field-node-site-10014" element
    And I fill in "Title" with "Bibendum Pharetra Inceptos"
    And I fill in "Summary" with "Cras Tristique Risus"
    And I fill in "Topic" with "Test topic 1 (10017)"
    And I select "Needs Review" from "Save as"

    When I am logged in as "test.editor"
    Then I edit test "[TEST] LP 1"
    Then I should not see an "#edit-delete" element
    Then save screenshot

    When I am logged in as a user with the "bypass node delete restriction, bypass node access" permission
    Then I edit test "[TEST] LP 1"
    Then I should see an "#edit-delete" element
    Then save screenshot

    When I am an anonymous user
    Then I send a GET request to "api/v1/node/test?site=10010"
    Then I should get a 200 HTTP response
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000?site=10010"
    Then I should get a 200 HTTP response
    And save screenshot
    And the response should be in JSON
    And the JSON node "links.self" should exist
    And the JSON node "links.self.href" should contain "api/v1/node/test"
    And the JSON node "data" should exist
    
    When I send a GET request to "/api/v1/route?&site=10010&path=/site-10010/test-lp-1"
    Then the JSON node "data.attributes.bundle" should be equal to "test"
    And the JSON node "data.attributes.uuid" should be equal to "99999999-aaaa-bbbb-ccc-000000000000"
    And the JSON node "data.attributes.section" should be equal to "10011"
    And the JSON node "data.attributes.entity_id" should be equal to "999999"
    And the JSON node "data.attributes.endpoint" should contain "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000"
    And the JSON node "links.self.href" should contain "api/v1/route?site=10010&path=/site-10010/test-lp-1"

    When I send a GET request to "/api/v1/node/test?site=10010&sort=-created"
    Then the JSON node "data[0].id" should be equal to "99999999-aaaa-bbbb-ccc-000000000000"
    Then the JSON node "data[0].attributes.moderation_state" should be equal to "published"
    And the JSON node "data[0].attributes.title" should be equal to "[TEST] LP 1"
    And the JSON node "data[0].attributes.moderation_state" should be equal to "published"
    And the JSON node "data[0].attributes.drupal_internal__nid" should be equal to "999999"
    And the JSON node "data[0].attributes.metatag_normalized" should exist
    And the JSON node "data[0].attributes.metatag_normalized[0].attributes.name" should be equal to "title"
    And the JSON node "data[0].attributes.metatag_normalized[0].attributes.content" should be equal to "[TEST] LP 1 | Single Digital Presence Content Management System"
    And the JSON node "data[0].attributes.metatag_normalized[1].attributes.href" should contain "/test-lp-1"
    And the JSON node "data[0].attributes.path" should exist
    And the JSON node "data[0].attributes.path.alias" should be equal to "/test-lp-1"
    And the JSON node "data[0].attributes.path.url" should be equal to "/test-lp-1"
    And the JSON node "data[0].attributes.path.origin_alias" should be equal to "/site-10010/test-lp-1"
    And the JSON node "data[0].attributes.path.origin_url" should be equal to "/test-lp-1"
    When I send a GET request to "api/v1/node/test?site=10010"
    And the JSON node "meta.count" should be equal to "1"

  @api @suggest
  Scenario: editors can access nodes that site-admin user created.
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
    When I go to "/node/999999/edit"
    Then I should get a 200 HTTP response
    When I go to "/node/999998/edit"
    Then I should get a 200 HTTP response
    When I go to "/node/999997/edit"
    Then I should get a 404 HTTP response

    When I am an anonymous user
    Then I send a GET request to "api/v1/node/test?site=10010"
    Then I should get a 200 HTTP response
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000001?site=10010"
    Then I should get a 200 HTTP response
    And save screenshot
    And the response should be in JSON
    And the JSON node "links.self" should exist
    And the JSON node "links.self.href" should contain "api/v1/node/test"
    And the JSON node "data" should exist

    When I send a GET request to "/api/v1/route?&site=10010&path=/site-10010/test-lp-1"
    Then the JSON node "data.attributes.bundle" should be equal to "test"
    And the JSON node "data.attributes.uuid" should be equal to "99999999-aaaa-bbbb-ccc-000000000001"
    And the JSON node "data.attributes.section" should be equal to "10011"
    And the JSON node "data.attributes.entity_id" should be equal to "999999"
    And the JSON node "data.attributes.endpoint" should contain "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000001"
    And the JSON node "links.self.href" should contain "api/v1/route?site=10010&path=/site-10010/test-lp-1"

    When I send a GET request to "/api/v1/node/test?site=10010&sort=-created"
    Then the JSON node "data[0].id" should be equal to "99999999-aaaa-bbbb-ccc-000000000002"
    Then the JSON node "data[0].attributes.moderation_state" should be equal to "published"
    And the JSON node "data[0].attributes.title" should be equal to "[TEST] LP 2"
    And the JSON node "data[0].attributes.moderation_state" should be equal to "published"
    And the JSON node "data[0].attributes.drupal_internal__nid" should be equal to "999998"
    And the JSON node "data[0].attributes.metatag_normalized" should exist
    And the JSON node "data[0].attributes.metatag_normalized[0].attributes.name" should be equal to "title"
    And the JSON node "data[0].attributes.metatag_normalized[0].attributes.content" should be equal to "[TEST] LP 2 | Single Digital Presence Content Management System"
    And the JSON node "data[0].attributes.metatag_normalized[1].attributes.href" should contain "/test-lp-2"
    And the JSON node "data[0].attributes.path" should exist
    And the JSON node "data[0].attributes.path.alias" should be equal to "/test-lp-2"
    And the JSON node "data[0].attributes.path.url" should be equal to "/test-lp-2"
    And the JSON node "data[0].attributes.path.origin_alias" should be equal to "/site-10010/test-lp-2"
    And the JSON node "data[0].attributes.path.origin_url" should be equal to "/test-lp-2"
    When I send a GET request to "api/v1/node/test?site=10010"
    And the JSON node "meta.count" should be equal to "2"