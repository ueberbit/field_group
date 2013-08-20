<?php
/**
 * @file
 * Contains \Drupal\field_group\FieldGroupFieldUi.
 */

namespace Drupal\field_group;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Provides, well we will see.
 */
class FieldGroupFieldUi {

  protected $storageController;


  private $fields;
  private $entity_type;
  private $bundle;
  private $display_mode;
  private $view_mode;
  // private $form_state;

  public function __construct(EntityStorageControllerInterface $storage_controller) {
    $this->storageController = $storage_controller;
  }

  public function setFormData($fields, $entity_type, $bundle, $display_mode, $view_mode) {
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->display_mode = $display_mode;
    $this->view_mode = $view_mode;
    $this->fields = $fields;
    // $this->form_state = $form_state;
  }


  /**
   * This one needs a rewrite...
   *
   *
   */
  public function submitForm(&$form, &$form_state) {
    $values = $form_state['input']['fields'];
    // dsm($form_state);

    // // dsm($form_state);

    // // TODO: SAVE EMPTY FIELD GROUPS.
    $save_to_field_group = array();
    // $parent = '';
    // $already_saved = array();
    // TODO: Save field group changes on existing ones.
    foreach ($this->getDraggableFields() as $delta => $field_name) {
      // dsm($values[$field_name]);
      if(!empty($values[$field_name]['parent'])) {
        $parent = $values[$field_name]['parent'];
        $values[$parent]['fields'][] = $field_name;
      }
    }

    // If _add_new_field_group is used.
    if(!empty($values['_add_new_field_group']['field_group_name'])) {
      $this->createFieldGroup($values['_add_new_field_group']);
    }
    foreach($form['#field_groups'] as $field_group_name => $field_group) {
      $values[$field_group_name]['field_group_name'] = $field_group_name;
      // dsm($field_group);
      $this->updateFieldGroup($values[$field_group_name]);
    }

    // // TODO: This is still crappy. Empty field groups are not stored correctly.
    // // TODO: Make it possible to save _add_new_field_group together with nested fields.
    // //       This might become a little tricky :/
    // $storage_controller = \Drupal::entityManager()->getStorageController('field_group');
    // // Save existing field_groups.
    // foreach ($save_to_field_group as $field_group_id => $value) {
    //   // $id = $this->getEntityType() . '.' . $this->getBundle() . '.' . $this->getDisplayMode() . '.' . $this->getViewmode() . '.' . $field_group_id;
    //   $id = $field_group_id;
    //   dsm($field_group_id);
    //   $already_saved += array($id => $id);
    //   // dsm($id);
    //   $entity = $storage_controller->load($id);
    //   // dsm($entity);
    //   $entity->set('parent', $parent);
    //   $entity->set('fields', $value);
    //   $entity->set('widget_type', $values[$field_group_id]['type']);
    //   $entity->save($entity);
    // }

    // // We assume that a field_group which is not saved above, is an empty one.
    // foreach ($this->getMachineNames() as $key => $key) {
    //   if(!in_array($key, $already_saved)) {
    //     $entity = $storage_controller->load($key);
    //     $entity->set('parent', $parent);
    //     $entity->set('fields', array());
    //     $entity->set('widget_type', $values[$key]['type']);
    //     $entity->save($entity);
    //   }
    // }
  }

  /**
   * Needs at least ID parameter.
   */
  public function createFieldGroup($values) {

    $id = $this->entity_type . '.' . $this->bundle . '.' . $this->display_mode . '.' . $this->view_mode . '.' . $values['field_group_name'];
    $values['id'] = (isset($values['id']) && !empty($values['id'])) ? $values['id'] : $id;
    $values['entity_type'] = $this->entity_type;
    $values['bundle'] = $this->bundle;
    $values['display_mode'] = $this->display_mode;
    $values['view_mode'] = $this->view_mode;
    $values['field_group_name'] = 'field_group_' . $values['field_group_name'];

    $storageController = \Drupal::entityManager()->getStorageController('field_group');
    $entity = $storageController->create($values);
    return $entity->save();
  }

  private function updateFieldGroup($values) {
    $storageController = \Drupal::entityManager()->getStorageController('field_group');
    $entity = $storageController->loadByProperties(
      array(
        'field_group_name' => $values['field_group_name'],
        'entity_type' => $this->entity_type,
        'bundle' => $this->bundle,
        'display_mode' => $this->display_mode,
        'view_mode' => $this->view_mode
      )
    );
    $entity = reset($entity);
    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
    $entity->save();
  }

  private function deleteFieldGroup($fieldGroup) {
    $this->deleteFieldGroupsMultiple(array($fieldGroup));
  }
  private function deleteFieldGroupsMultiple($fieldGroups) {
    $storageController = \Drupal::entityManager()->getStorageController('field_group');
    $storageController->delete($fieldGroups);
  }

  /**
   * This should be saveFieldgroup()
   *
   * TODO: We need a
   *        - updateFieldGroup
   *        - saveFieldGroup
   *        - deleteFieldGroup
   *
   */
  // private function addNewFieldGroup($values, $fields) {
  //   $machine_name = 'field_group_' . $values['field_group_name'];
  //   $field_group_id = $this->getEntityType() . '.' . $this->getBundle() . '.' . $this->getDisplayMode() . '.' . $this->getViewmode() . '.' . $machine_name;

  //   $widget_type = $values['type'];
  //   $parent = $values['parent'];
  //   dsm($parent);

  //   $uuid = new Uuid();
  //   $field_group = array(
  //     'id' => $field_group_id,
  //     // 'field_order' => $field_order,
  //     // 'field_groups' => $field_group,
  //     'entity_type' => $this->getEntityType(),
  //     'bundle' => $this->getBundle(),
  //     'display_mode' => $this->getDisplayMode(),
  //     'view_mode' => $this->getViewMode(),
  //     'widget_type' => $widget_type,
  //     'fields' => $fields,
  //     'parent' => $parent,
  //     'machine_name' => $machine_name,
  //     'uuid' => $uuid->generate(),
  //     'label' => 'test',
  //   );

  //   $storage_controller = \Drupal::entityManager()->getStorageController('field_group');
  //   $entity = $storage_controller->create($field_group);
  //   $entity->save();
  // }

  private function getEntityType() {
    return $this->entity_type;
  }
  private function getBundle() {
    return $this->bundle;
  }
  private function getDisplayMode() {
    return $this->display_mode;
  }
  private function getViewMode() {
    return $this->view_mode;
  }

  /**
   * Fetch fieldGroup id's by given properies.
   */
  public function getFieldGroups() {
    $storage_controller = \Drupal::entityManager()->getStorageController('field_group');
    $field_groups = $storage_controller->loadByProperties(
      array(
        'entity_type' => $this->entity_type,
        'bundle' => $this->bundle,
        'display_mode' => $this->display_mode,
        'view_mode' => $this->view_mode
      )
    );
    return $field_groups;
  }

  public function getMachineNames() {
    $machine_names = array();
    $field_groups = $this->getFieldGroups();

    // $storage_controller = \Drupal::entityManager()->getStorageController('field_group');
    // $field_groups = isset($field_groups) ? $storage_controller->loadMultiple($field_groups) : array();
    foreach($field_groups as $field_group) {
      $machine_names[$field_group->field_group_name] = $field_group->id;
    }
    return $machine_names;
  }

  public function getDraggableFields() {
    $fieldGroupKeys = array_keys($this->getMachineNames());
    return array_merge($this->fields['fields'], $this->fields['extra'], array(
        '_add_new_field',
        '_add_existing_field',
        '_add_new_field_group',
      ),
      $fieldGroupKeys
    );
  }




  private function getId() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->display_mode . '.' . $this->view_mode;
  }

  /**
   * Generate fieldgroup isntances for field_ui.
   */
  public function getFieldgroupInstance($keys = array()) {
    $groups = array();

    foreach($keys as $delta => $name) {
      $id = 'field_group.' . $name;
      $field_group = \Drupal::config($id)->get();
      $field_group_name = $field_group['field_group_name'];
      $groups[$field_group['field_group_name']] = array(
        '#attributes' => array(
          'class' => array(
            'draggable',
            'field-group',
            'new-group2',
          ),
        ),
        '#row_type' => 'field_group',
        '#region_callback' => 'field_group_field_overview_row_region',
        // '#js_settings' => array(
        //   'rowHandler' => 'field_group',
        //   'defaultPlugin' => 'div',
        // ),
        'human_name' => array(
          // TODO: should be dynamically.
          '#markup' => $field_group['label'],
        ),
        'weight' => array(
          '#type' => 'textfield',
          // TODO: Save and reade weight attribtue.
          '#default_value' => $field_group['weight'],
          '#size' => 3,
          '#attributes' => array(
            'class' => array(
              'field-weight',
            ),
          ),
          '#title_display' => 'invisible',
          '#title' => 'Weight for ' + $field_group_name,
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => 'Parent for ' + $field_group_name,
            '#title_display' => 'invisible',
            '#options' => array(),
            '#empty_value' => '',
            '#attributes' => array(
              'class' => array(
                'field-parent',
              ),
            ),
            '#parents' => array(
              'fields',
              $field_group_name,
              'parent',
            ),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $field_group_name,
            '#attributes' => array(
              'class' => array(
                'field-name',
              ),
            ),
          ),
        ),
        'label' => array(
          // '#type' => 'select',
          // '#title' => 'Label display for Image',
          // '#title_display' => 'invisible',
          // '#options' => array(
          //   'above' => 'Above',
          //   'inline' => 'Inline',
          //   'hidden' => '- Hidden -'
          // ),
          // '#default_value' => 'above',
          '#markup' => 'No settings available yet',
        ),
        // 'type' => array(
        //   // TODO: Should be the selected Widget?
        //   '#markup' => 'Field Group',
        // ),
        'plugin' => array(
          'type' => array(
            // TODO: This should be dynamically.
            '#type' => 'select',
            '#title' => 'Widget for new field group',
            '#title_display' => 'invisible',
            '#default_value' => \Drupal::config($id)->get('type'),
            '#options' => field_group_widget_options(),
            // TODO: Check how to make this translatable.
            '#empty_option' => '- Select a field group type -',
            '#description' => 'Form element to edit the data.',
            '#parents' => array(
              'fields',
              $field_group_name,
              'type'
            ),
            '#attributes' => array(
              'class' => array(
                ' field-plugin-type',
              ),
            ),
          ),
          // 'settings_edit_form' => array(),
          'settings_edit_form' => array(),
          '#title' => 'Widget for Fieldgroup',
          // '#cell_attributes' => array(
          //   'colspan' => 1,
          // ),
          // '#prefix' => '<div class="add-new-placeholder"> </div>',
        ),
        'settings_summary' => array(
          '#prefix' => '<div class="field-plugin-summary">',
          '#markup' => 'We need some generic method to generate this.',
          '#sufix' => '</div>',
          '#cell_attributes' => array(
            'class' => array(
              'field-plugin-summary-cell',
            ),
          ),
        ),
        // 'operations' => array(
        //   '#markup' => l('delete', 'field_group/delete'),
        // ),
      );
    }

    return $groups;

  }

  public function getRowRegion($row) {
    switch ($row['#row_type']) {
      case 'add_new_field':
        return 'hidden';
    }
  }

  public function field_group_add_group() {
    $name = '_add_new_field_group';

    return array(
      '#attributes' => array(
        'class' => array(
          'draggable',
          // 'tabledrag-leaf',
          'add-new',
        ),
      ),
      '#row_type' => 'add_new_field',
      '#region_callback' => array($this, 'getRowRegion'),
      'label' => array(
        '#type' => 'textfield',
        '#title' => 'New field label',
        '#title_display' => 'invisible',
        '#size' => 15,
        '#description' => 'Label',
        '#prefix' => '<div class="label-input"><div class="add-new-placeholder">Add new field group</div>',
        '#suffix' => '</div>',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => 4,
        '#size' => 3,
        '#title_display' => 'invisible',
        '#title' => 'Weight for new field',
        '#attributes' => array(
          'class' => array(
            'field-weight',
          ),
        ),
        '#prefix' => '<div class="add-new-placeholder"> </div>',
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => t('Parent for default field'),
          '#title_display' => 'invisible',
          '#options' => array(),
          '#empty_value' => '',
          '#attributes' => array(
            'class' => array(
              'field-parent'
            ),
          ),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
          '#parents' => array(
            'fields',
            $name,
            'parent'
          ),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array(
            'class' => array(
              'field-name'
            ),
          ),
        ),
      ),
      'field_group_name' => array(
        '#type' => 'machine_name',
        '#title' => 'New field name',
        '#title_display' => 'invisible',
        '#field_prefix' => '<span dir="ltr">field_group_',
        '#field_suffix' => '</span>‎',
        '#size' => 15,
        '#description' => 'A unique machine-readable name containing letters, numbers, and underscores.',
        '#maxlength' => 26,
        '#prefix' => '<div class="add-new-placeholder"> </div>',
        '#machine_name' => array(
          'source' => array(
            'fields',
            '_add_new_field_group',
            'label',
          ),
          'exists' => '_field_group_field_name_exists',
          'standalone' => TRUE,
          'label' => '',
        ),
        '#required' => FALSE,
      ),
      'type' => array(),
      'type' => array(
        '#type' => 'select',
        '#title' => 'Widget for new field group',
        '#title_display' => 'invisible',
        '#options' => $this->field_group_widget_options(),
        // TODO: Check how to make this translatable.
        '#empty_option' => '- Select a field group type -',
        '#description' => 'Form element to edit the data.',
        '#attributes' => array(
          'class' => array(
            'widget-type-select',
          ),
        ),
        '#cell_attributes' => array(
          'colspan' => 3,
        ),
        '#prefix' => '<div class="add-new-placeholder"> </div>',
      ),
      'translatable' => array(
        '#type' => 'value',
        '#value' => FALSE,
      ),
    );
  }

  private function field_group_widget_options() {
    $widget_options = array();
    $widgets = \Drupal::service('plugin.manager.field_group')->getDefinitions();
    // dsm($widgets);
    // dsm(\Drupal::service('plugin.manager.field_group')->getDefinitions());
    foreach($widgets as $widget_name => $widget) {
      $field_type = key(array_flip($widget['field_types']));
      if($field_type == 'field_group') {
        $widget_options[$widget_name] = $widget_name;
      }
    }
    return $widget_options;
  }

}