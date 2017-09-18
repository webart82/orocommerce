@fixture-OroProductBundle:product-quantity-negative.yml

Feature: Product quantity negative value and unit precision
  Input different values in quantity field
  Login as AmandaRCole@example.org on frontend commerce

  Scenario: Check product quantity negative value
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    And I type "-432423" in "ProductQuantityField"
    Then ProductQuantityField field should has 432423 value
    And I type "43244." in "ProductQuantityField"
    Then ProductQuantityField field should has 43244.0000 value
    And I fill "ProductLineItemForm" with:
      | Unit | item             |
    And I type "44." in "ProductQuantityField"
    Then ProductQuantityField field should has 44.000000 value
    And I type "123.456789" in "ProductQuantityField"
    Then ProductQuantityField field should has 123.456789 value
    And I type "1-+2+3.g4*567&f8999" in "ProductQuantityField"
    Then ProductQuantityField field should has 123.456789 value
