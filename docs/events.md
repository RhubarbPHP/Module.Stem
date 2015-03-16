Model Eventing
===

There are three types of events to consider in modelling: internal lifecycle events, property change notifications
and emitted events.

## 1. Lifecycle Events

Lifecycle events refer to calls to empty protected methods at key stages in a model's lifecycle. To handle the event
you simply override the relevant method. Current lifecycle events are:

OnLoaded
:   Called when a model is fetched from the repository, a collection or if the model's raw data is replaced
such that the model now represents a different record (i.e. it's unique identifier changes)

OnDataImported
:   Called when model data is replaced

BeforeSave
:   Called after the model has been validated but before it is committed to the repository

AfterSave
:   Called after the model has been committed to the repository

OnDeleted
:   Called after the model has been deleted

## 2. Property Change Notifications

The `ModelState` class (base class of `Model`) supports raising events when properties of the model change. These events
may be attached externally from an observer or internally. Externally a class manipulating a model may want to be
notified if during processing a key property changes, rather than keep track of this itself. Internally this is used
to make sure that the model state is kept consistent and is often used to rebuild status and total columns rather
than waiting until the model is saved.

To attach a change notification handler call the `AddPropertyChangedNotificationHandler()`:

~~~ php
$bucket = new CoalBucket();
$bucket->AddPropertyChangeNotification( "LumpsOfCoal", function( $new )
{
    if ( $new == 0 )
    {
        // Do something quick - the bucket is empty!
    }
} );

// The method call below might cause the function above to run - it may not. It depends on what `LumpsOfCoal` drops to.
$bucket->StockTheFire();
~~~

In practice this is most often used internally with a model to make sure that various computed properties are kept up to
date. This pattern is used in place of calculating computed properties when models are saved. This ensures that
the model is consistent even before the model is saved and reduces the overall number of calls make to save the model.

~~~ php
class Member extends Model
{
	protected function AttachPropertyChangedNotificationHandlers()
	{
        $this->AddPropertyChangedNotificationHandler( [ "Height", "Weight" ], function( $new, $propertyName, $old )
            {
                $this->UpdateBMI();
            } );
	}
}
~~~

Notice here that you can pass an array of column names instead of a single column name. Also notice that the
callback function can take up to three parameters, the new value, the name of the changing property
(used when the same callback is registered for multiple columns) and the previous value. They are in this order
as this roughly matches the frequency that the parameters are needed.

## 3. Emitted Events

Models can raise events that let outside observers know when key changes take place. Often code that
has to integrate with external systems, for example email or SMS, gets baked into the Model to make
sure that if the model changes the integration events occur. However following the pattern of separating
concerns this code should not be in the model.

Similarly code that applies business logic rules *between models* can become hard to maintain when the code that
expresses those rules is spread over a large number of model classes. It may be appropriate to let a business rules
class co-ordinate activities based on an event trigger.

### Raising an event

Simply call the `RaiseEvent()` method. Pass the name of the event and any secondary parameters which
you need to pass to the event handler. Note that while the event name is abitrary it is good to have a
convention - UpperCamelCase seems as good as any.

~~~ php
public function ResetPassword( $password )
{
    $this->Password = $password;

    $this->RaiseEvent( "PasswordReset" );
}
~~~

### Handling an event

There are two ways to handle events from a model. If you have a reference to the actual instance of
the model object throwing the event you can handle it directly:

~~~ php
$modelFiringEvent->AttachEventHandler( "EventName", function()
{
	// Do event stuff in here.
} );
~~~

All model events also get marshalled through the ModelEventManager class. Handle events by asking
the model event manager to listen for you can execute your callback. This is useful for situations
where you want to handle an event thrown by any instance of a model, not just one specific model
record.

~~~ php
ModelEventManager::AttachEventHandler( "User", "PasswordReset", function( $model )
{
    // Email the user to let them know...
} );

ModelEventManager::AttachEventHandler( "User", [ "SubscriptionCancel", "SubscriptionTypeChange" ], function( $model )
{
    // Email the user to let them know...
} );
~~~

`AttachEventHandler()` takes the alias name of the model and the either a single event name as a string or any number of
 event names in an array as the first two parameters and the callback function as the third. 

The first parameter to your event handler is the model raising the event. Subsequent parameters
raised in the events
will be passed next in the same order.

### Delaying Events Until After Save

Occasionally it's important to ensure events get thrown just after a model is saved. Instead of handling this by
setting flags and picking up on these in the `AfterSave()` method you can simply call `RaiseEventAfterSave()` with
the same parameters as you would call `RaiseEvent`:

~~~ php
protected function BeforeSave()
{
    if( $this->HasPropertyChanged( "Amount" ) )
    {
        $this->RaiseEventAfterSave( "AmountChanged" );
    }
    parent::BeforeSave();
}
~~~

### Integration Events

Where the handler of an event needs to integrate with another system and there is a possibility that
it may take a long time or crash out, you should make sure that the integration is deferred until
after the model activity has completed. This will avoid circumstances where the model is left
inconsistent due to a crashing integration.