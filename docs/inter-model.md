Inter-Model Strategy
===

The purpose of our model classes is to house all of our state changing application logic. However a
change to a model may be a trigger for other models to change as well. Consider these examples:

* A `Transaction` is added to a ledger. The `Account` balance needs to update.
* A `User` password is changed. An entry into the `Log` must be made.
* `Stock` adjustments are made when stock is received in. Orders waiting on the stock must be
flagged as awaiting despatch.
* A `Stock` item is flagged as "Not For Sale". All `Orders` containing that stock must be marked
"Held"

There are two types of inter model changes:

### 1. Updating 'children'

A model's children can be viewed as all other models related to this one as part of a one to many
relationship. For example an `Invoice` model would have `InvoiceLine` models as children as part of
the `$Lines` property. A `Customer` model would have `Order` models as children as part of the
`$Orders` property.

In these cases the best practice is for the model to manipulate it's children directly as we can
assume a parent knows all about their children.

~~~ php
class Invoice extends Model
{
	// ...
	public function ApplyDiscount( $percentage )
	{
		foreach( $this->Lines as $line )
		{
			$line->ApplyDiscount( $percentage );
		}
	}
	// ...
}
~~~

### 2. Updating a 'parent' or 'stranger'

A 'parent' is a model which can be navigated to through a one to one relationship. For example the
parent of an `InvoiceLine` is an `Invoice`.

An 'stranger' is a model which is not navigable from the model via a relationship.
For example the `InvoiceLine` model has no relationship with the `Address` model and so should not
control it directly.

In both cases the event should be handled by an external agent that is listening for the event being
raised on the `ModelEventManager`. The best candidate for this agent is the application's
`SolutionSchema` object where the models and relationships are registered.

~~~ php
class MyDatabaseSchema extends SolutionSchema
{
	public function __construct()
	{
		parent::__construct( 0.1 );

		$this->AddModel( "GoodsReceived", "My\App\Model\Contracts\GoodsReceived" );
		$this->AddModel( "Contract", "My\App\Model\Contracts\Contract" );

		ModelEventManager::AttachEventHandler( "GoodsReceived", "AfterSave", function( $goodsReceived )
		{
			// Update the contract totals as a goods received has been adjusted.
			$goodsReceived->Contract->UpdateTotals();
		});
	}
}
~~~

The solution schema knows best how the models inter-relate and so can be seen as good choice of
co-ordinator for inter-model events.